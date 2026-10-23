<?php

declare(strict_types=1);

namespace App\Models;

use App\Post\Type;
use Expansa\Facades\Db;
use Expansa\Facades\Image;
use Expansa\Facades\Safe;
use Expansa\Filesystem\MimeType;
use Expansa\Patterns;
use Expansa\Support\Error;
use Expansa\Support\Url;

/**
 * Expansa administration Media API.
 */
class Media
{
    /**
     * Folder of the uploaded originals; resized copies are in `i/{width}x{height}/`.
     */
    private const string DIRECTORY = EX_STORAGE . 'i/original/';

    /**
     * Fields of a file post: the name on disk and what upload() learned about the file.
     */
    private const array FIELDS = ['file', 'mime', 'bytes', 'width', 'height', 'sizes'];

    /**
     * Columns of a file post the library shows.
     */
    private const array COLUMNS = ['id [Int]', 'title', 'author_id [Int]', 'created_at'];

    /**
     * Get a page of the library, the newest first. A search finds the titles that have every
     * word of it, or a word starting with it, through the FULLTEXT index of the posts table.
     *
     * @param int    $page
     * @param string $search
     * @param int    $perPage
     * @return array<int, array<string, mixed>> Items for the library, see item().
     */
    public static function get(int $page = 1, string $search = '', int $perPage = 30): array
    {
        $type = Type::get('files');
        if (! $type instanceof Type) {
            return [];
        }

        $where = [
            'ORDER' => ['id' => 'DESC'],
            'LIMIT' => [(max(1, $page) - 1) * $perPage, $perPage],
        ];

        $words = preg_split('/[^\p{L}\p{N}]+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words !== []) {
            $where['MATCH'] = [
                'columns' => ['title', 'content'],
                'keyword' => implode(' ', array_map(fn (string $word) => "+$word*", $words)),
                'mode'    => 'boolean',
            ];
        }

        return self::items($type, Db::select($type->table, self::COLUMNS, $where) ?? []);
    }

    /**
     * Get one item of the library, e.g. a file just uploaded.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $type = Type::get('files');
        $row  = $type instanceof Type ? Db::get($type->table, self::COLUMNS, ['id' => $id]) : null;

        return is_array($row) ? self::items($type, [$row])[0] ?? null : null;
    }

    /**
     * Turn rows of file posts into items of the library: two queries for the fields and the authors,
     * no access to the disk.
     *
     * @param Type                              $type
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function items(Type $type, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $fields  = self::fields($type, array_column($rows, 'id'));
        $authors = [];
        $ids     = array_unique(array_column($rows, 'author_id'));
        foreach (Db::select('users', ['id [Int]', 'showname', 'login'], ['id' => $ids]) ?: [] as $user) {
            $authors[$user['id']] = $user['showname'] ?: $user['login'];
        }

        return array_map(function (array $row) use ($fields, $authors) {
            $field     = $fields[$row['id']] ?? [];
            $basename  = $field['file'] ?? '';
            $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
            $mime      = $field['mime'] ?? '';
            $bytes     = (int) ($field['bytes'] ?? 0);
            $icon      = sprintf('%sassets/images/files/%s.svg', EX_DASHBOARD, self::icon($mime, $extension));

            $sizes = [];
            foreach (array_filter(explode(',', $field['sizes'] ?? '')) as $key) {
                $size = self::sizes()[$key] ?? null;
                if ($size !== null) {
                    $sizes[$key] = [
                        'url'    => Url::toUrl(sprintf('%si/%dx%d/%s', EX_STORAGE, $size['width'], $size['height'], $basename)),
                        'width'  => $size['width'],
                        'height' => $size['height'],
                    ];
                }
            }

            return [
                'id'           => $row['id'],
                'title'        => (string) $row['title'],
                'filename'     => $basename,
                'extension'    => $extension,
                'mime'         => $mime,
                'url'          => $basename !== '' ? Url::toUrl(self::DIRECTORY . $basename) : '',
                'icon'         => Url::toUrl($icon),
                'width'        => isset($field['width']) ? (int) $field['width'] : null,
                'height'       => isset($field['height']) ? (int) $field['height'] : null,
                'size'         => $bytes,
                'sizeHumanize' => self::humanize($bytes),
                'created'      => (string) $row['created_at'],
                'author'       => $authors[$row['author_id']] ?? '',
                'sizes'        => $sizes,
            ];
        }, $rows);
    }

    /**
     * Get the fields of file posts by post ID.
     *
     * @param Type  $type
     * @param int[] $ids
     * @return array<int, array<string, string>>
     */
    private static function fields(Type $type, array $ids): array
    {
        $fields = [];
        $where  = ['post_id' => $ids, 'key' => self::FIELDS];
        foreach (Db::select("{$type->table}_fields", ['post_id [Int]', 'key', 'value'], $where) ?: [] as $row) {
            $fields[$row['post_id']][$row['key']] = (string) $row['value'];
        }

        return $fields;
    }

    /**
     * Get the image sizes of the site by key, the ones with both dimensions.
     *
     * @return array<string, array{width: int, height: int}>
     */
    private static function sizes(): array
    {
        $sizes = [];
        foreach ((array) Patterns\Registry::get('images') as $key => $size) {
            $width  = Safe::absint($size['width'] ?? 0);
            $height = Safe::absint($size['height'] ?? 0);
            if ($width && $height) {
                $sizes[(string) $key] = ['width' => $width, 'height' => $height];
            }
        }

        return $sizes;
    }

    /**
     * Get the name of the icon of a file kind, see the icons in `assets/images/files/`.
     *
     * @param string $mime
     * @param string $extension
     * @return string
     */
    private static function icon(string $mime, string $extension): string
    {
        return match (true) {
            $extension === 'svg'                                 => 'svg',
            str_starts_with($mime, 'image/')                     => 'image',
            str_starts_with($mime, 'video/')                     => 'video',
            str_starts_with($mime, 'audio/')                     => 'audio',
            in_array($extension, ['csv', 'txt', 'zip'], true)    => $extension,
            in_array($extension, ['doc', 'docx', 'odt'], true)   => 'doc',
            in_array($extension, ['xls', 'xlsx', 'ods'], true)   => 'xls',
            default                                              => 'default',
        };
    }

    /**
     * Human-readable size, as Filesystem shows it: `512 b`, `1.25 Mb`.
     *
     * @param int $bytes
     * @return string
     */
    private static function humanize(int $bytes): string
    {
        $units = ['b', 'Kb', 'Mb', 'Gb'];
        $unit  = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $unit < 3) {
            $value /= 1024;
            $unit++;
        }

        return round($value, $unit === 0 ? 0 : 2) . ' ' . $units[$unit];
    }

    /**
     * Uploads the file transmitted in the form to the Expansa downloads folder
     * and creates an entry about the file in the database (adds the file to the library).
     * It works with elements of the global variable $_FILES, the function needs to specify
     * an array of file data and the function itself will upload the file to the Expansa
     * downloads folder. If the file type is an image, it will create smaller copies of it.
     *
     * @param array $file Array that represents a `$_FILES` upload array.
     * @return Error|int
     */
    public static function upload(array $file): int|Error
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            return new Error('media_upload', t('An error occurred while uploading the file. Please try again.'));
        }

        $mime = self::mimeFromFilename($file['name'] ?? '');
        if (! $mime) {
            return new Error('media_upload', t('Sorry, you are not allowed to upload this file type.'));
        }

        $directory = self::DIRECTORY;
        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            return new Error('media_upload', t('Failed to create the uploads directory.'));
        }

        $basename = self::uniqueBasename($directory, self::sanitizeFilename($file['name'] ?? ''));
        if (! $basename) {
            return new Error('media_upload', t('The file name can\'t be empty or contain invalid characters.'));
        }

        $filepath = $directory . $basename;
        if (! move_uploaded_file($file['tmp_name'], $filepath)) {
            return new Error('media_upload', t('Something went wrong. The upload failed.'));
        }

        return self::finalize($filepath, $basename, $mime);
    }

    /**
     * Downloads a file from an external URL into the Expansa downloads folder and adds it to the library.
     *
     * @param string $url External file URL.
     * @return Error|int
     */
    public static function grab(string $url): int|Error
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return new Error('media_grab', t('Can\'t fetch the file because the URL is invalid.'));
        }

        $basename = self::sanitizeFilename(basename((string) parse_url($url, PHP_URL_PATH)));
        $mime     = self::mimeFromFilename($basename);
        if (! $basename || ! $mime) {
            return new Error('media_grab', t('Can\'t fetch the file because it doesn\'t have a valid extension.'));
        }

        $directory = self::DIRECTORY;
        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            return new Error('media_grab', t('Failed to create the uploads directory.'));
        }

        $basename = self::uniqueBasename($directory, $basename);
        $filepath = $directory . $basename;

        $handle = fopen($filepath, 'wb');
        $ch     = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_OPTIONS    => CURLSSLOPT_NATIVE_CA,
        ]);
        $downloaded = curl_exec($ch);
        $curlError  = curl_error($ch);
        curl_close($ch);
        fclose($handle);

        if (! $downloaded) {
            @unlink($filepath);
            return new Error('media_grab', $curlError ?: t('Something went wrong while uploading the file.'));
        }

        return self::finalize($filepath, $basename, $mime);
    }

    /**
     * Removes a media file (and its resized variants) and its library entry.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $type     = Type::get('files');
        $post     = Post::get('files', $id);
        $basename = $type instanceof Type ? self::fields($type, [$id])[$id]['file'] ?? '' : '';
        if (! $post instanceof Post || ! $post->delete()) {
            return false;
        }

        if ($basename !== '') {
            $paths = [self::DIRECTORY . $basename];
            foreach (self::sizes() as $size) {
                $paths[] = sprintf('%si/%dx%d/%s', EX_STORAGE, $size['width'], $size['height'], $basename);
            }

            foreach ($paths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        return true;
    }

    /**
     * Creates the resized variants for image uploads and registers the file in the library.
     *
     * @param string $filepath Absolute path to the already-stored original file.
     * @param string $basename File name on disk, used as the post slug.
     * @param string $mime     Detected mime type of the file.
     * @return Error|int
     */
    private static function finalize(string $filepath, string $basename, string $mime): int|Error
    {
        $fields = [
            'file'  => $basename,
            'mime'  => $mime,
            'bytes' => (string) (int) filesize($filepath),
        ];

        if (str_starts_with($mime, 'image/')) {
            $dimensions = getimagesize($filepath);
            if ($dimensions !== false) {
                $fields['width']  = (string) $dimensions[0];
                $fields['height'] = (string) $dimensions[1];
            }

            $resized = [];
            foreach (self::sizes() as $key => $size) {
                $resizedPath = sprintf('%si/%dx%d/%s', EX_STORAGE, $size['width'], $size['height'], $basename);
                $resizedDir  = dirname($resizedPath);

                if (! is_dir($resizedDir) && ! mkdir($resizedDir, 0755, true)) {
                    continue;
                }

                try {
                    Image::load($filepath)->crop($size['width'], $size['height'])->save($resizedPath);
                    $resized[] = $key;
                } catch (\Throwable) {
                    // A failed resize shouldn't block the upload itself - the original file is already saved.
                }
            }

            if ($resized !== []) {
                $fields['sizes'] = implode(',', $resized);
            }
        }

        $post = Post::add('files', [
            'status' => 'publish',
            'title'  => pathinfo($basename, PATHINFO_FILENAME),
            'slug'   => $basename,
            'fields' => $fields,
        ]);

        if (! $post instanceof Post) {
            @unlink($filepath);
            return new Error('media_upload', t('Failed to save the file to the library.'));
        }

        return $post->id;
    }

    /**
     * Resolves a file's mime type from its extension against the allow-list, or null when
     * the extension isn't recognized/allowed.
     */
    private static function mimeFromFilename(string $filename): ?string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (! $extension) {
            return null;
        }

        foreach (new MimeType()->typesList as $extensions => $mime) {
            if (in_array($extension, explode('|', $extensions), true)) {
                return $mime;
            }
        }

        return null;
    }

    /**
     * Strips anything but letters/numbers/dots/dashes/underscores from a file name.
     */
    private static function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($filename));

        return trim($filename ?? '', '-');
    }

    /**
     * Appends a numeric suffix until the file name is unique within the given directory.
     */
    private static function uniqueBasename(string $directory, string $basename): string
    {
        if ($basename === '' || ! is_file($directory . $basename)) {
            return $basename;
        }

        $filename  = pathinfo($basename, PATHINFO_FILENAME);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $suffix    = 1;

        do {
            $suffix++;
            $candidate = $extension ? sprintf('%s-%d.%s', $filename, $suffix, $extension) : sprintf('%s-%d', $filename, $suffix);
        } while (is_file($directory . $candidate));

        return $candidate;
    }
}
