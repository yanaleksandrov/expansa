<?php

declare(strict_types=1);

namespace Plugins\FileManager;

use Expansa\Http\Exceptions\HttpError;
use Expansa\Support\Humanize;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

/**
 * Files and folders inside the root folder of the file manager. Paths come from the browser
 * relative to the root (`app/Api`, empty for the root itself) and never leave it: `..`, absolute
 * paths, symlinks pointing outside and hidden paths are rejected. Failures throw HttpError 422
 * with a message for the user.
 *
 * @package Plugins\FileManager
 */
final class Explorer
{
    /**
     * Icons of the files by extension, the rest get `ph-file`.
     */
    private const array ICONS = [
        'ph-file-zip'   => ['zip', 'gz', 'tar', 'rar', '7z'],
        'ph-file-image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico', 'bmp'],
        'ph-file-code'  => ['php', 'json', 'xml', 'yml', 'yaml', 'sh'],
        'ph-file-text'  => ['txt', 'md', 'log', 'csv', 'ini', 'env', 'conf'],
        'ph-file-pdf'   => ['pdf'],
        'ph-file-js'    => ['js', 'mjs', 'ts'],
        'ph-file-css'   => ['css', 'scss'],
        'ph-file-html'  => ['html'],
    ];

    /**
     * Types shown in the browser by the preview, by extension.
     */
    private const array PREVIEWS = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'bmp'  => 'image/bmp',
        'pdf'  => 'application/pdf',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mp3'  => 'audio/mpeg',
        'ogg'  => 'audio/ogg',
        'wav'  => 'audio/wav',
    ];

    /**
     * Largest text shown by the preview, in bytes.
     */
    private const int TEXT_LIMIT = 1048576;

    /**
     * Most entries found by the search and most entries it looks through.
     */
    private const int SEARCH_LIMIT = 200;

    private const int SEARCH_SCAN_LIMIT = 50000;

    /**
     * Path of the trash relative to the root, always hidden; empty when the trash is outside.
     */
    private string $trashPath = '';

    public function __construct(

        /**
         * Absolute path of the root folder with forward slashes, without the trailing slash.
         */
        public private(set) string $root {
            set {
                $root = realpath($value);
                if ($root === false || ! is_dir($root)) {
                    throw new InvalidArgumentException("The root folder $value does not exist.");
                }

                $this->root = str_replace('\\', '/', $root);
            }
        },

        /**
         * Patterns of fnmatch() hiding paths relative to the root or names at any depth.
         *
         * @var string[]
         */
        private readonly array $hidden = [],

        /**
         * Lowercase extensions that cannot be uploaded, created, renamed to or extracted.
         *
         * @var string[]
         */
        private readonly array $blocked = [],

        /**
         * Where deleted entries go, null to delete them at once.
         */
        public readonly ?Trash $trash = null,
    ) {
        $trash = str_replace('\\', '/', (string) $trash?->folder);
        if ($trash !== '' && str_starts_with($trash . '/', $this->root . '/')) {
            $this->trashPath = trim(substr($trash, strlen($this->root)), '/');
        }
    }

    /**
     * Entries of a folder with the subfolders of it and of each parent folder for the tree.
     *
     * @param string $path Folder relative to the root.
     * @return array{path: string, items: list<array<string, mixed>>, branches: array<string, string[]>}
     */
    public function list(string $path): array
    {
        $folder   = $this->resolve($path, folder: true);
        $relative = $this->relative($folder);

        $items = [];
        foreach (new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS) as $entry) {
            if (! $this->isHidden($this->join($relative, $entry->getFilename()))) {
                $items[] = $this->describe($entry);
            }
        }

        $branches = ['' => $this->folders('')];
        $parent   = '';
        foreach ($relative === '' ? [] : explode('/', $relative) as $segment) {
            $parent            = $this->join($parent, $segment);
            $branches[$parent] = $this->folders($parent);
        }

        return ['path' => $relative, 'items' => $items, 'branches' => $branches];
    }

    /**
     * Names of the subfolders of a folder, for the tree.
     *
     * @param string $path Folder relative to the root.
     * @return string[]
     */
    public function folders(string $path): array
    {
        $folder   = $this->resolve($path, folder: true);
        $relative = $this->relative($folder);

        $folders = [];
        foreach (new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir() && ! $this->isHidden($this->join($relative, $entry->getFilename()))) {
                $folders[] = $entry->getFilename();
            }
        }
        natcasesort($folders);

        return array_values($folders);
    }

    /**
     * Entries whose names contain the query, in a folder and its subfolders.
     *
     * @param string $path  Folder relative to the root.
     * @param string $query
     * @return array{path: string, items: list<array<string, mixed>>, complete: bool}
     */
    public function search(string $path, string $query): array
    {
        $folder = $this->resolve($path, folder: true);
        $items  = [];
        $seen   = 0;
        foreach ($this->iterate($folder, RecursiveIteratorIterator::SELF_FIRST, $this->isVisible(...)) as $entry) {
            if (++$seen > self::SEARCH_SCAN_LIMIT || count($items) >= self::SEARCH_LIMIT) {
                return ['path' => $this->relative($folder), 'items' => $items, 'complete' => false];
            }

            if (mb_stripos($entry->getFilename(), $query) !== false) {
                $items[] = [...$this->describe($entry), 'dir' => $this->relative(str_replace('\\', '/', $entry->getPath()))];
            }
        }

        return ['path' => $this->relative($folder), 'items' => $items, 'complete' => true];
    }

    /**
     * Properties of an entry for the details panel.
     *
     * @param string $path Folder relative to the root.
     * @param string $name
     * @return array<string, mixed>
     */
    public function details(string $path, string $name): array
    {
        $entry  = $this->entry($path, $name);
        $info   = new SplFileInfo($entry);
        $folder = $info->isDir();
        $owner  = function_exists('posix_getpwuid') ? (posix_getpwuid((int) $info->getOwner())['name'] ?? '') : '';
        $group  = function_exists('posix_getgrgid') ? (posix_getgrgid((int) $info->getGroup())['name'] ?? '') : '';
        $mime   = ! $folder && function_exists('mime_content_type') ? (string) @mime_content_type($entry) : '';

        return [
            ...$this->describe($info),
            'path'     => $this->relative($entry),
            'type'     => $folder ? t('Folder') : ($mime ?: t('File')),
            'created'  => date('M d, Y h:i A', $info->getCTime()),
            'owner'    => $owner ?: (string) $info->getOwner(),
            'group'    => $group ?: (string) $info->getGroup(),
            'rights'   => $this->rights($info->getPerms(), $folder),
            'preview'  => ! $folder && isset(self::PREVIEWS[strtolower($info->getExtension())]),
            'exact'    => $folder ? '' : t(':count bytes', number_format($info->getSize(), 0, '.', ' ')),
            'link'     => $info->isLink(),
        ];
    }

    /**
     * Size of a folder with its contents, hidden entries included.
     *
     * @param string $path Folder relative to the root.
     * @param string $name
     * @return array{bytes: int, size: string, files: int}
     */
    public function size(string $path, string $name): array
    {
        $entry = $this->entry($path, $name);
        $bytes = is_dir($entry) ? 0 : (int) filesize($entry);
        $files = is_dir($entry) ? 0 : 1;
        if (is_dir($entry)) {
            foreach ($this->iterate($entry, RecursiveIteratorIterator::LEAVES_ONLY) as $item) {
                $bytes += $item->isFile() ? $item->getSize() : 0;
                $files += $item->isFile() ? 1 : 0;
            }
        }

        return ['bytes' => $bytes, 'size' => Humanize::fromBytes($bytes), 'files' => $files];
    }

    /**
     * Text of a file for the preview.
     *
     * @param string $path Folder relative to the root.
     * @param string $name
     * @return string
     */
    public function read(string $path, string $name): string
    {
        $entry = $this->entry($path, $name);
        if (is_dir($entry) || filesize($entry) > self::TEXT_LIMIT) {
            throw $this->error(':name is too large to preview.', $name);
        }

        $text = (string) file_get_contents($entry);
        if (str_contains(substr($text, 0, 8000), "\0") || ! mb_check_encoding($text, 'UTF-8')) {
            throw $this->error(':name is not a text file.', $name);
        }

        return $text;
    }

    /**
     * Create an empty file or a folder.
     *
     * @param string $path   Parent folder relative to the root.
     * @param string $name
     * @param bool   $folder
     * @return void
     */
    public function create(string $path, string $name, bool $folder = false): void
    {
        $target = $this->resolve($path, folder: true) . '/' . $this->allowed($this->name($name), $folder);
        if (file_exists($target)) {
            throw $this->error(':name already exists.', $name);
        }

        if (! ($folder ? @mkdir($target, 0755) : @touch($target))) {
            throw $this->error('Failed to create :name.', $name);
        }
    }

    /**
     * Names taken in a folder, to ask what to do with them before a paste or an upload.
     *
     * @param string   $path  Folder relative to the root.
     * @param string[] $names
     * @return string[]
     */
    public function conflicts(string $path, array $names): array
    {
        $folder = $this->resolve($path, folder: true);

        return array_values(array_filter(
            $names,
            static fn (string $name) => $name !== '' && file_exists("$folder/" . basename($name))
        ));
    }

    /**
     * Save uploaded files to a folder.
     *
     * @param string               $path     Folder relative to the root.
     * @param array<string, mixed> $files    Item of $_FILES, single or multiple.
     * @param Conflict             $conflict What to do with a taken name.
     * @return void
     */
    public function upload(string $path, array $files, Conflict $conflict = Conflict::Keep): void
    {
        $folder = $this->resolve($path, folder: true);
        foreach ((array) ($files['tmp_name'] ?? []) as $index => $tmp) {
            $name = $this->allowed($this->name((string) (is_array($files['name']) ? $files['name'][$index] : $files['name'])));
            if (! is_uploaded_file((string) $tmp)) {
                throw $this->error('Failed to upload :name.', $name);
            }

            $target = $this->target($folder, $name, $conflict);
            if ($target !== null && ! move_uploaded_file((string) $tmp, $target)) {
                throw $this->error('Failed to upload :name.', $name);
            }
        }
    }

    /**
     * Copy or move entries to another folder.
     *
     * @param string   $from     Source folder relative to the root.
     * @param string[] $names
     * @param string   $to       Target folder relative to the root.
     * @param bool     $move
     * @param Conflict $conflict What to do with a taken name.
     * @return void
     */
    public function paste(string $from, array $names, string $to, bool $move = false, Conflict $conflict = Conflict::Keep): void
    {
        $target = $this->resolve($to, folder: true);
        foreach ($names as $name) {
            $source = $this->entry($from, $name);
            if ($target === $source || str_starts_with($target . '/', $source . '/')) {
                throw $this->error(':name cannot be put inside itself.', $name);
            }

            if (dirname($source) === $target && ($move || $conflict !== Conflict::Keep)) {
                continue;
            }

            // replacing a folder that holds the source would delete the source too
            if ($conflict === Conflict::Replace && str_starts_with($source . '/', $target . '/' . basename($source) . '/')) {
                throw $this->error(':name cannot be put inside itself.', $name);
            }

            $destination = $this->target($target, basename($source), $conflict);
            $put         = fn (string $to): bool => $move ? $this->relocate($source, $to) : $this->copy($source, $to);
            $done        = $destination === null || $put($destination);
            if (! $done) {
                throw $this->error('Failed to paste :name.', $name);
            }
        }
    }

    /**
     * Rename an entry inside its folder.
     *
     * @param string $path Folder relative to the root.
     * @param string $name Current name.
     * @param string $to   New name.
     * @return void
     */
    public function rename(string $path, string $name, string $to): void
    {
        $source = $this->entry($path, $name);
        $target = dirname($source) . '/' . $this->allowed($this->name($to), is_dir($source));
        if ($target === $source) {
            return;
        }

        // a case-only rename on a case-insensitive disk finds the entry itself
        if (file_exists($target) && strcasecmp($target, $source) !== 0) {
            throw $this->error(':name already exists.', $to);
        }

        if (! @rename($source, $target)) {
            throw $this->error('Failed to rename :name.', $name);
        }
    }

    /**
     * Change the permissions of entries, not of the contents of folders.
     *
     * @param string   $path  Folder relative to the root.
     * @param string[] $names
     * @param string   $mode  Octal like `0644` or `755`.
     * @return void
     */
    public function chmod(string $path, array $names, string $mode): void
    {
        if (! preg_match('/^0?[0-7]{3}$/', $mode)) {
            throw $this->error(':name is not a valid permission.', $mode);
        }

        foreach ($names as $name) {
            if (! @chmod($this->entry($path, $name), (int) octdec($mode))) {
                throw $this->error('Failed to change the permissions of :name.', $name);
            }
        }
    }

    /**
     * Delete entries: to the trash when it is on, otherwise with their contents at once.
     *
     * @param string   $path  Folder relative to the root.
     * @param string[] $names
     * @return void
     */
    public function delete(string $path, array $names): void
    {
        foreach ($names as $name) {
            $source = $this->entry($path, $name);
            $done   = $this->trash
                ? $this->relocate($source, $this->trash->reserve($this->relative(dirname($source)), $name) . "/$name")
                : $this->remove($source);

            if (! $done) {
                throw $this->error('Failed to delete :name.', $name);
            }
        }
    }

    /**
     * Entries of the trash for the trash view.
     *
     * @return list<array<string, mixed>>
     */
    public function trashed(): array
    {
        return array_map(fn (array $item) => [
            ...$this->describe(new SplFileInfo($item['entry'])),
            'id'      => $item['id'],
            'dir'     => $item['path'],
            'mtime'   => $item['deleted'],
            'deleted' => date('M d, Y h:i A', $item['deleted']),
        ], $this->trash?->all() ?? []);
    }

    /**
     * Move entries from the trash back to their folders, created again when missing.
     *
     * @param string[] $ids
     * @return void
     */
    public function restore(array $ids): void
    {
        foreach ($ids as $id) {
            $item = $this->trash?->get($id) ?? throw $this->error(':name is not found.', $id);

            $folder = $this->root . '/' . $item['path'];
            $unsafe = $this->isHidden($item['path']) || str_contains("/{$item['path']}/", '/../');
            if ($unsafe || (! is_dir($folder) && ! @mkdir($folder, 0755, true))) {
                throw $this->error('Failed to restore :name.', $item['name']);
            }

            if (! $this->relocate($item['entry'], $this->free($folder, $item['name']))) {
                throw $this->error('Failed to restore :name.', $item['name']);
            }
            $this->trash->forget($id);
        }
    }

    /**
     * Delete entries of the trash for good, all of them without ids.
     *
     * @param string[] $ids
     * @return void
     */
    public function purge(array $ids = []): void
    {
        $items = $ids === []
            ? ($this->trash?->all() ?? [])
            : array_filter(array_map(fn (string $id) => $this->trash?->get($id), $ids));
        foreach ($items as $item) {
            if (! $this->remove($item['entry'])) {
                throw $this->error('Failed to delete :name.', $item['name']);
            }
            $this->trash?->forget($item['id']);
        }
    }

    /**
     * Pack entries into a ZIP archive in the same folder.
     *
     * @param string   $path  Folder relative to the root.
     * @param string[] $names
     * @param string   $name  Archive name, `.zip` is added when missing.
     * @return void
     */
    public function archive(string $path, array $names, string $name): void
    {
        $name = $this->name(str_ends_with(strtolower($name), '.zip') ? $name : "$name.zip");

        $this->zip($this->free($this->resolve($path, folder: true), $name), $path, $names);
    }

    /**
     * Unpack a ZIP archive into a new folder named after it.
     *
     * @param string $path Folder relative to the root.
     * @param string $name Archive name.
     * @return void
     */
    public function extract(string $path, string $name): void
    {
        $source = $this->entry($path, $name);
        $zip    = new ZipArchive();
        if (is_dir($source) || $zip->open($source, ZipArchive::RDONLY) !== true) {
            throw $this->error(':name is not a ZIP archive.', $name);
        }

        // ZipArchive does not stop entries like ../index.php from leaving the target folder
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if ($entry === '' || $entry[0] === '/' || preg_match('~(^|/)\.\.(/|$)|^[a-z]:~i', $entry)) {
                $zip->close();
                throw $this->error(':name contains unsafe paths.', $name);
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (! str_ends_with($entry, '/') && in_array($extension, $this->blocked, true)) {
                $zip->close();
                throw $this->error(':name contains files with a forbidden extension.', $name);
            }
        }

        $target = $this->free(dirname($source), pathinfo($source, PATHINFO_FILENAME));
        $done   = @mkdir($target, 0755) && $zip->extractTo($target);
        $zip->close();

        if (! $done) {
            throw $this->error('Failed to extract :name.', $name);
        }
    }

    /**
     * Send entries to the browser and end the request: a single file as is, the rest as a ZIP archive.
     *
     * @param string   $path  Folder relative to the root.
     * @param string[] $names
     * @return never
     */
    public function download(string $path, array $names): never
    {
        $source = count($names) === 1 ? $this->entry($path, $names[0]) : '';
        if ($source !== '' && is_file($source)) {
            $this->send($source, basename($source));
        }

        $archive = (string) tempnam(sys_get_temp_dir(), 'fm');
        $this->zip($archive, $path, $names);

        $name = $source !== '' ? basename($source) : (basename($this->resolve($path, folder: true)) ?: 'files');

        $this->send($archive, "$name.zip", delete: true);
    }

    /**
     * Show a file in the browser and end the request; scripts of SVG and PDF files do not run.
     *
     * @param string $path Folder relative to the root.
     * @param string $name
     * @return never
     */
    public function preview(string $path, string $name): never
    {
        $source = $this->entry($path, $name);
        $type   = self::PREVIEWS[strtolower(pathinfo($source, PATHINFO_EXTENSION))] ?? null;
        if ($type === null || ! is_file($source)) {
            throw $this->error(':name cannot be previewed.', $name);
        }

        $this->send($source, basename($source), type: $type);
    }

    /**
     * Absolute path of a path relative to the root, which must exist and not be hidden.
     *
     * @param string $path
     * @param bool   $folder Whether it must be a folder.
     * @return string
     */
    private function resolve(string $path, bool $folder = false): string
    {
        $segments = array_filter(explode('/', str_replace('\\', '/', $path)), static fn (string $segment) => $segment !== '');
        $current  = '';
        foreach ($segments as $segment) {
            $current = $this->join($current, $segment);
            if ($segment === '..' || str_contains($segment, "\0") || $this->isHidden($current)) {
                throw $this->error(':name is not found.', $path);
            }
        }

        $real   = str_replace('\\', '/', (string) realpath($this->root . '/' . implode('/', $segments)));
        $inside = $real === $this->root || str_starts_with($real, $this->root . '/');
        if (! $inside || ($folder && ! is_dir($real))) {
            throw $this->error(':name is not found.', $path);
        }

        return $real;
    }

    /**
     * Absolute path of an entry of a folder, without following the entry if it is a symlink.
     *
     * @param string $path Folder relative to the root.
     * @param string $name
     * @return string
     */
    private function entry(string $path, string $name): string
    {
        $folder = $this->resolve($path, folder: true);
        $entry  = $folder . '/' . $this->name($name);
        if ((! file_exists($entry) && ! is_link($entry)) || $this->isHidden($this->join($this->relative($folder), $name))) {
            throw $this->error(':name is not found.', $name);
        }

        return $entry;
    }

    /**
     * Whether a path relative to the root is hidden by the settings or is the trash.
     *
     * @param string $relative
     * @return bool
     */
    private function isHidden(string $relative): bool
    {
        if ($this->trashPath !== '' && ($relative === $this->trashPath || str_starts_with($relative, "$this->trashPath/"))) {
            return true;
        }

        $name = basename($relative);

        return array_any($this->hidden, static fn (string $pattern) => fnmatch($pattern, $relative) || fnmatch($pattern, $name));
    }

    /**
     * Whether an entry found at any depth is not hidden, for the filters of iterate().
     *
     * @param SplFileInfo $entry
     * @return bool
     */
    private function isVisible(SplFileInfo $entry): bool
    {
        return ! $this->isHidden($this->relative(str_replace('\\', '/', $entry->getPathname())));
    }

    /**
     * Path relative to the root.
     *
     * @param string $path Absolute path inside the root with forward slashes.
     * @return string
     */
    private function relative(string $path): string
    {
        return ltrim(substr($path, strlen($this->root)), '/');
    }

    /**
     * Join a folder relative to the root and a name.
     *
     * @param string $path
     * @param string $name
     * @return string
     */
    private function join(string $path, string $name): string
    {
        return $path === '' ? $name : "$path/$name";
    }

    /**
     * Check a name of a single entry: not empty, without slashes and not `.` or `..`.
     *
     * @param string $name
     * @return string
     */
    private function name(string $name): string
    {
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..' || strlen($name) > 255 || strpbrk($name, "/\\\0") !== false) {
            throw $this->error(':name is not a valid name.', $name);
        }

        return $name;
    }

    /**
     * Check that a new file does not get a forbidden extension.
     *
     * @param string $name
     * @param bool   $folder Folders have no extension.
     * @return string
     */
    private function allowed(string $name, bool $folder = false): string
    {
        if (! $folder && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $this->blocked, true)) {
            throw $this->error('Files like :name are forbidden by the settings.', $name);
        }

        return $name;
    }

    /**
     * Path for an entry put into a folder by the conflict mode, null to skip it.
     *
     * @param string   $folder
     * @param string   $name
     * @param Conflict $conflict
     * @return string|null
     */
    private function target(string $folder, string $name, Conflict $conflict): ?string
    {
        $path = "$folder/$name";
        if (! file_exists($path) && ! is_link($path)) {
            return $path;
        }

        return match ($conflict) {
            Conflict::Keep    => $this->free($folder, $name),
            Conflict::Skip    => null,
            Conflict::Replace => $this->remove($path) ? $path : throw $this->error('Failed to replace :name.', $name),
        };
    }

    /**
     * Free path in a folder: `photo.jpg`, then `photo-2.jpg`, `photo-3.jpg`.
     *
     * @param string $folder
     * @param string $name
     * @return string
     */
    private function free(string $folder, string $name): string
    {
        $path = "$folder/$name";
        if (! file_exists($path)) {
            return $path;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $extension = $extension === '' ? '' : ".$extension";
        $filename  = substr($name, 0, strlen($name) - strlen($extension));

        $suffix = 2;
        while (file_exists($path = "$folder/$filename-$suffix$extension")) {
            $suffix++;
        }

        return $path;
    }

    /**
     * Move an entry, by copying and deleting it when it goes to another disk.
     *
     * @param string $source
     * @param string $target Free path.
     * @return bool
     */
    private function relocate(string $source, string $target): bool
    {
        return @rename($source, $target) || ($this->copy($source, $target) && $this->remove($source));
    }

    /**
     * Copy a file or a folder with its contents.
     *
     * @param string $source
     * @param string $target Free path.
     * @return bool
     */
    private function copy(string $source, string $target): bool
    {
        if (! is_dir($source)) {
            return @copy($source, $target);
        }

        if (! @mkdir($target, 0755)) {
            return false;
        }

        $offset = strlen($source);
        foreach ($this->iterate($source, RecursiveIteratorIterator::SELF_FIRST) as $item) {
            $path = $target . substr($item->getPathname(), $offset);
            if (! ($item->isDir() ? @mkdir($path, 0755) : @copy($item->getPathname(), $path))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete a file, a symlink or a folder with its contents.
     *
     * @param string $path
     * @return bool
     */
    private function remove(string $path): bool
    {
        if (! is_dir($path) || is_link($path)) {
            return @unlink($path);
        }

        foreach ($this->iterate($path, RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            $deleted = $item->isDir() && ! $item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            if (! $deleted) {
                return false;
            }
        }

        return @rmdir($path);
    }

    /**
     * Write entries of a folder into a new ZIP archive, folders with their contents except hidden ones.
     *
     * @param string   $archive Path of the archive.
     * @param string   $path    Folder relative to the root.
     * @param string[] $names
     * @return void
     */
    private function zip(string $archive, string $path, array $names): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw $this->error('Failed to create :name.', basename($archive));
        }

        foreach ($names as $name) {
            $source = $this->entry($path, $name);
            if (! is_dir($source)) {
                $zip->addFile($source, basename($source));
                continue;
            }

            $offset = strlen(dirname($source)) + 1;
            $zip->addEmptyDir(basename($source));
            foreach ($this->iterate($source, RecursiveIteratorIterator::SELF_FIRST, $this->isVisible(...)) as $item) {
                $local = str_replace('\\', '/', substr($item->getPathname(), $offset));
                $item->isDir() ? $zip->addEmptyDir($local) : $zip->addFile($item->getPathname(), $local);
            }
        }

        if (! $zip->close()) {
            throw $this->error('Failed to create :name.', basename($archive));
        }
    }

    /**
     * Contents of a folder at any depth, without following symlinks.
     *
     * @param string                          $folder
     * @param int                             $mode   RecursiveIteratorIterator mode.
     * @param (callable(SplFileInfo): bool)|null $filter Entries to keep, a folder left out is not entered.
     * @return RecursiveIteratorIterator<RecursiveDirectoryIterator>
     */
    private function iterate(string $folder, int $mode, ?callable $filter = null): RecursiveIteratorIterator
    {
        $iterator = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
        if ($filter !== null) {
            $iterator = new RecursiveCallbackFilterIterator($iterator, static fn (SplFileInfo $entry) => $filter($entry));
        }

        return new RecursiveIteratorIterator($iterator, $mode, RecursiveIteratorIterator::CATCH_GET_CHILD);
    }

    /**
     * Entry of a listing.
     *
     * @param SplFileInfo $entry
     * @return array<string, mixed>
     */
    private function describe(SplFileInfo $entry): array
    {
        $folder = $entry->isDir();
        $bytes  = $folder ? 0 : (int) $entry->getSize();

        return [
            'name'        => $entry->getFilename(),
            'folder'      => $folder,
            'icon'        => $folder ? 'ph-folder' : $this->icon($entry->getExtension()),
            'mtime'       => $entry->getMTime(),
            'modified'    => date('M d, Y h:i A', $entry->getMTime()),
            'permissions' => substr(sprintf('%o', $entry->getPerms()), -4),
            'access'      => $entry->isWritable() ? t('Read & Write') : t('Read only'),
            'bytes'       => $bytes,
            'size'        => $folder ? '' : Humanize::fromBytes($bytes),
        ];
    }

    /**
     * Permissions as `rwxr-xr-x`.
     *
     * @param int  $perms
     * @param bool $folder
     * @return string
     */
    private function rights(int $perms, bool $folder): string
    {
        $rights = $folder ? 'd' : '-';
        foreach ([0400, 0200, 0100, 040, 020, 010, 04, 02, 01] as $index => $bit) {
            $rights .= $perms & $bit ? 'rwx'[$index % 3] : '-';
        }

        return $rights;
    }

    /**
     * Stream a file and end the request.
     *
     * @param string      $file
     * @param string      $name   File name for the browser.
     * @param bool        $delete Delete the file after sending, for a temporary archive.
     * @param string|null $type   Shown in the browser with this type, otherwise downloaded.
     * @return never
     */
    private function send(string $file, string $name, bool $delete = false, ?string $type = null): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $disposition = $type === null ? 'attachment' : 'inline';

        header('Content-Type: ' . ($type ?? 'application/octet-stream'));
        header("Content-Disposition: $disposition; filename*=UTF-8''" . rawurlencode($name));
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        // the built-in PDF viewer of Chrome does not open in a sandbox
        if ($type !== 'application/pdf') {
            header("Content-Security-Policy: sandbox; default-src 'none'; img-src 'self' data:; media-src 'self'; "
                . "style-src 'unsafe-inline'");
        }

        readfile($file);
        if ($delete) {
            unlink($file);
        }

        exit;
    }

    /**
     * Phosphor icon of a file by its extension.
     *
     * @param string $extension
     * @return string
     */
    private function icon(string $extension): string
    {
        $extension = strtolower($extension);

        return array_find_key(self::ICONS, static fn (array $extensions) => in_array($extension, $extensions, true)) ?? 'ph-file';
    }

    /**
     * Error for the user with the name of the entry.
     *
     * @param string $message Message with `:name`.
     * @param string $name
     * @return HttpError
     */
    private function error(string $message, string $name): HttpError
    {
        return new HttpError(422, t($message, $name));
    }
}
