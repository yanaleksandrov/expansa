<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

use Expansa\Url;
use Expansa\Filesystem\Traits\Entry;

class EntryHandler
{
    use Entry;

    /**
     * Trait that provides file-related properties.
     */
    public function __construct(string $path)
    {
        $this->path   = rtrim($path, '/');
        $this->exists = $this->isExists();

        if ($this->exists) {
            $this->filename   = $this->getFileName();
            $this->basename   = $this->getBaseName();
            $this->dirname    = $this->getDirName();
            $this->dirpath    = $this->getDirPath();
            $this->hash       = $this->getHash();
            $this->mime       = $this->getMime();
            $this->type       = $this->getType();
            $this->url        = $this->getUrl();
            $this->extension  = $this->getExtension();
            $this->modified   = $this->getModifiedTime();
            $this->sizeB      = $this->getSize();
            $this->sizeKb     = $this->reformat($this->sizeB, 'Kb');
            $this->sizeMb     = $this->reformat($this->sizeB, 'Mb');
            $this->sizeGb     = $this->reformat($this->sizeB, 'Gb');
            $this->size       = $this->humanize($this->sizeB);
            $this->permission = $this->getPermission();
            $this->errors     = [];
        }
    }

    /**
     * Get the file name from a path.
     */
    protected function getFileName(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Get the base name from a path.
     */
    protected function getBaseName(): string
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

    /**
     * Get the directory name from a path.
     */
    protected function getDirName(): string
    {
        return basename(pathinfo($this->path, PATHINFO_DIRNAME));
    }

    /**
     * Get the directory name from a path.
     */
    protected function getDirPath(): string
    {
        return pathinfo($this->path, PATHINFO_DIRNAME);
    }

    /**
     * Create unique hash of the file.
     *
     * @return string
     */
    protected function getHash(): string
    {
        if (is_dir($this->path)) {
            return hash('md5', $this->path);
        }
        return hash_file('md5', $this->path);
    }

    /**
     * Get the MIME type of file.
     */
    protected function getMime(): string
    {
        if (is_dir($this->path)) {
            return '';
        }
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->path);
    }

    /**
     * Get the type of file.
     */
    protected function getType(): string
    {
        return filetype($this->path);
    }

    /**
     * Get the type of file.
     */
    protected function getUrl(): string
    {
        return url(str_replace(EX_PATH, '', $this->path));
    }

    /**
     * Get the extension of a file.
     */
    protected function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file's last modification time.
     */
    protected function getModifiedTime(): string
    {
        return date('Y-m-d H:i:s', filemtime($this->path));
    }

    /**
     * Get the size of a file.
     */
    protected function getSize(): int
    {
        if (is_dir($this->path)) {
            $size  = 0;
            $files = $this->files('*', 9999);

            foreach ($files as $file) {
                $size += filesize($file);
            }

            return $size;
        }
        return filesize($this->path) ?? 0;
    }

    /**
     * Get the file permission.
     */
    protected function getPermission(): int
    {
        return (int) substr(sprintf('%o', fileperms($this->path)), -4);
    }

    /**
     * Determine if a file exists.
     */
    protected function isExists(): bool
    {
        if (is_dir($this->path)) {
            return is_writable($this->path);
        }
        return is_file($this->path) && is_writable($this->path);
    }

    /**
     * Create file if not exists.
     */
    protected function createFile(): void
    {
        if (! $this->exists) {
            $dirpath = dirname($this->path);

            // create directory if not exists
            if (! is_dir($dirpath)) {
                mkdir($dirpath, 0755, true);
            }

            // create file if not exists
            touch($this->path);
        }
    }

    /**
     * Convert bytes to human-readable file size.
     *
     * @param int $bytes
     * @param string $format
     * @return float
     */
    protected function reformat(int $bytes, string $format = ''): float
    {
        $format  = ucfirst(strtolower($format));
        $formats = [
            'Kb' => 1,
            'Mb' => 2,
            'Gb' => 3,
            'Tb' => 4,
        ];

        $result    = $bytes / pow(1024, $formats[$format] ?? 0);
        $precision = match ($format) {
            'Kb'    => 0,
            'Mb'    => 2,
            'Gb',
            'Tb'    => 3,
            default => 2,
        };

        return round($result, $precision);
    }

    /**
     * Convert bytes to human-readable file size.
     *
     * @param int $bytes
     * @return string
     */
    protected function humanize(int $bytes): string
    {
        $units     = ['b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < 3) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, $unitIndex === 0 ? 0 : 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Retrieves the maximum upload file size in bytes.
     *
     * @return int The size in bytes.
     */
    protected function getMaxUploadSizeInBytes(): int
    {
        $maxUploadSize = ini_get('upload_max_filesize');
        $last          = strtolower($maxUploadSize[ strlen($maxUploadSize) - 1 ]);
        $maxUploadSize = intval($maxUploadSize);

        switch ($last) {
            case 'g':
                $maxUploadSize *= 1024;
            case 'm':
                $maxUploadSize *= 1024;
            case 'k':
                $maxUploadSize *= 1024;
        }

        return $maxUploadSize;
    }

    /**
     * Filters string for valid path syntax, with optional trailing slash.
     *
     * @param  mixed  $path Path to file or directory.
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        // remove whitespace, spaces, leading and trailing slashes
        $path = trim(preg_replace('/\s+/', '', (string) $path));

        // convert all invalid slashes to one single forward slash
        return strtr(
            $path,
            [
                '//'   => '/',
                '\\'   => '/',
                '\\\\' => '/',
            ]
        );
    }

    /**
     * Filters string for valid URL characters.
     *
     * @param string $value Value to change
     * @return string
     */
    public function sanitizeUrl(string $value): string
    {
        return strval(filter_var(trim($value), FILTER_SANITIZE_URL));
    }

    /**
     * Sanitizer & normalize file name: remove special chars & spaces.
     * Check for a file with the same name, add a prefix until we find a free name.
     *
     * @param string $name
     * @return string
     */
    public function sanitizeName(string $name): string
    {
        // remove not allowed symbols & letters
        $filename = mb_convert_encoding(htmlspecialchars(mb_strtolower($name)), 'UTF-8');
        $formats  = mb_convert_encoding(
            'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª',
            'ISO-8859-1',
            'UTF-8'
        );
        $replace = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyrr                                 ';

        // remove whitespaces
        $filename = str_replace(
            ['-----', '----', '---', '--'],
            '-',
            str_replace(' ', '-', trim(strtr($filename, $formats, $replace)))
        );

        // find existing files with same name & add prefix
        $filename = sprintf('%s.%s', $filename, $this->extension);
        $filename = str_replace(' ', '-', $filename);

        $path = sprintf('%s%s', $this->dirpath, $filename);
        if (is_file($path)) {
            $i      = 1;
            $prefix = '-' . $i;

            while (is_file($this->dirpath . $filename . $prefix . '.' . $this->extension)) {
                $prefix = '-' . ++$i;
            }
            $filename = sprintf('%s%s.%s', $filename, $prefix, $this->extension);
        }

        return $filename;
    }
}
