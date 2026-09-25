<?php

declare(strict_types=1);

namespace App\Models;

use App\Query\Query;
use App\Url;
use Expansa\Debug\Error;
use Expansa\Facades\Disk;
use Expansa\Facades\Image;
use Expansa\Facades\Safe;
use Expansa\Filesystem\MimeType;
use Expansa\Patterns;

/**
 * Expansa administration Media API.
 */
class Media
{
    /**
     * Get media files.
     *
     * @param array $args
     * @return string|array
     */
    public static function get(array $args = []): string|array
    {
        $args = [ 'type' => 'files', 'page' => 1, 'per_page' => 30, ...$args ];

        return Query::apply($args, function ($posts) {
            if (! is_array($posts)) {
                return $posts;
            }

            $sizes = Patterns\Registry::get('images');
            foreach ($posts as $i => $post) {
                if (! is_array($post) || ! is_array($sizes)) {
                    continue;
                }

                $filepath = sprintf('%si/original/%s', EX_STORAGE, $post['slug'] ?? '');
                if (file_exists($filepath)) {
                    $file      = Disk::file($filepath);
                    $iconPath  = sprintf('%sassets/images/files/%s.svg', EX_DASHBOARD, $file->type ?? 'default');
                    $posts[$i] = [
                        ...$post,
                        ...[
                            'url'          => Url::fromPath($filepath),
                            'icon'         => Url::fromPath($iconPath),
                            'height'       => $file->height,
                            'width'        => $file->width,
                            'path'         => $file->path,
                            'extension'    => $file->extension,
                            'basename'     => $file->basename,
                            'filename'     => $file->filename,
                            'mime'         => $file->mime,
                            'type'         => $file->type,
                            'size'         => $file->size,
                            'sizeKb'       => $file->sizeKb,
                            'sizeMb'       => $file->sizeMb,
                            'sizeHumanize' => $file->sizeHumanize,
                        ],
                    ];
                }

                foreach ($sizes as $key => $size) {
                    $width  = Safe::absint($size['width'] ?? 0);
                    $height = Safe::absint($size['height'] ?? 0);
                    if (! $width || ! $height) {
                        continue;
                    }

                    $filepath = sprintf('%si/%sx%s/%s', EX_STORAGE, $width, $height, $post['slug'] ?? '');
                    if (file_exists($filepath)) {
                        $file     = Disk::file($filepath);
                        $iconPath = sprintf('%sassets/images/files/%s.svg', EX_DASHBOARD, $file->type ?? 'default');

                        $posts[$i]['sizes'][$key] = [
                            'url'          => Url::fromPath($filepath),
                            'icon'         => Url::fromPath($iconPath),
                            'width'        => $width,
                            'height'       => $height,
                            'path'         => $file->path,
                            'extension'    => $file->extension,
                            'basename'     => $file->basename,
                            'filename'     => $file->filename,
                            'mime'         => $file->mime,
                            'type'         => $file->type,
                            'size'         => $file->size,
                            'sizeKb'       => $file->sizeKb,
                            'sizeMb'       => $file->sizeMb,
                            'sizeHumanize' => $file->sizeHumanize,
                        ];
                    }
                }
            }

            return $posts;
        });
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
            return new Error('media_upload', t('An error occurred while uploading the file, please try again.'));
        }

        $mime = self::mimeFromFilename($file['name'] ?? '');
        if (! $mime) {
            return new Error('media_upload', t('Sorry, you are not allowed to upload this file type.'));
        }

        $directory = EX_STORAGE . 'i/original/';
        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            return new Error('media_upload', t('Failed to create the uploads directory.'));
        }

        $basename = self::uniqueBasename($directory, self::sanitizeFilename($file['name'] ?? ''));
        if (! $basename) {
            return new Error('media_upload', t('File name must not contain illegal characters and must not be empty.'));
        }

        $filepath = $directory . $basename;
        if (! move_uploaded_file($file['tmp_name'], $filepath)) {
            return new Error('media_upload', t('Something went wrong, upload is failed.'));
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
            return new Error('media_grab', t('The file cannot be grabbed because the URL is not valid.'));
        }

        $basename = self::sanitizeFilename(basename((string) parse_url($url, PHP_URL_PATH)));
        $mime     = self::mimeFromFilename($basename);
        if (! $basename || ! $mime) {
            return new Error('media_grab', t('The file cannot be grabbed because it does not contain a valid extension.'));
        }

        $directory = EX_STORAGE . 'i/original/';
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
        ]);
        $downloaded = curl_exec($ch);
        $curlError  = curl_error($ch);
        curl_close($ch);
        fclose($handle);

        if (! $downloaded) {
            @unlink($filepath);
            return new Error('media_grab', $curlError ?: t('Something went wrong when uploading the file.'));
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
        $post = Post::get('files', $id);
        if (! $post instanceof Post) {
            return false;
        }

        if (! Post::delete('files', $id)) {
            return false;
        }

        $sizes = Patterns\Registry::get('images');
        @unlink(sprintf('%si/original/%s', EX_STORAGE, $post->slug));

        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                $width  = Safe::absint($size['width'] ?? 0);
                $height = Safe::absint($size['height'] ?? 0);
                if ($width && $height) {
                    @unlink(sprintf('%si/%sx%s/%s', EX_STORAGE, $width, $height, $post->slug));
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
        $sizes = Patterns\Registry::get('images');

        if (str_starts_with($mime, 'image/') && is_array($sizes)) {
            foreach ($sizes as $size) {
                $width  = Safe::absint($size['width'] ?? 0);
                $height = Safe::absint($size['height'] ?? 0);
                if (! $width || ! $height) {
                    continue;
                }

                $resizedPath = sprintf('%si/%sx%s/%s', EX_STORAGE, $width, $height, $basename);
                $resizedDir  = dirname($resizedPath);

                if (! is_dir($resizedDir) && ! mkdir($resizedDir, 0755, true)) {
                    continue;
                }

                try {
                    Image::load($filepath)->crop($width, $height)->save($resizedPath);
                } catch (\Throwable) {
                    // A failed resize shouldn't block the upload itself - the original file is already saved.
                }
            }
        }

        $post = Post::add('files', [
            'status' => 'publish',
            'title'  => pathinfo($basename, PATHINFO_FILENAME),
            'slug'   => $basename,
            'fields' => [
                'mime' => $mime,
            ],
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
