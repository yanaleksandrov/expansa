<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Internal;

use Expansa\Filesystem\Contracts\Entry;
use Expansa\Filesystem\Exceptions\NotFound;
use Expansa\Filesystem\Exceptions\OperationFailed;
use Expansa\Support\Url;

/**
 * Base of a file and a directory: path parts and metadata read from the disk on access,
 * so they stay current after writes and a new object costs nothing until it is read.
 *
 * @internal
 * @package Expansa\Filesystem\Internal
 */
abstract class AbstractEntry implements Entry
{
    public function __construct(

        /**
         * Full path without the trailing slash.
         */
        public protected(set) string $path {
            set => rtrim($value, '/\\');
        },
    ) {}

    abstract public bool $exists { get; }

    abstract public int $bytes { get; }

    /**
     * Name without the directory and the extension.
     */
    public string $filename {
        get => pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Name with the extension.
     */
    public string $basename {
        get => basename($this->path);
    }

    /**
     * Name of the parent directory.
     */
    public string $dirname {
        get => basename(dirname($this->path));
    }

    /**
     * Full path of the parent directory.
     */
    public string $dirpath {
        get => dirname($this->path);
    }

    public string $extension {
        get => pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Entry type from filetype(): `file`, `dir`, `link`; empty for a missing entry.
     */
    public string $type {
        get => $this->exists ? (string) filetype($this->path) : '';
    }

    public string $url {
        get => Url::toUrl($this->path);
    }

    /**
     * Last modification time as `Y-m-d H:i:s`, empty for a missing entry.
     */
    public string $modified {
        get => $this->exists ? date('Y-m-d H:i:s', (int) filemtime($this->path)) : '';
    }

    /**
     * Permissions as octal digits read as a number: 644, 755.
     */
    public int $permission {
        get => $this->exists ? (int) substr(sprintf('%o', fileperms($this->path)), -4) : 0;
    }

    /**
     * Human-readable size: `512 b`, `1.25 Mb`.
     */
    public string $size {
        get {
            $units = ['b', 'Kb', 'Mb', 'Gb'];
            $bytes = $this->bytes;
            $unit  = 0;

            while ($bytes >= 1024 && $unit < 3) {
                $bytes /= 1024;
                $unit++;
            }

            return round($bytes, $unit === 0 ? 0 : 2) . ' ' . $units[$unit];
        }
    }

    public float $sizeKb {
        get => round($this->bytes / 1024);
    }

    public float $sizeMb {
        get => round($this->bytes / 1024 ** 2, 2);
    }

    public float $sizeGb {
        get => round($this->bytes / 1024 ** 3, 3);
    }

    /**
     * Get a free name in the directory of the entry: sanitized, with the extension of the entry.
     *
     * @param string $name Name without the extension.
     * @return string Name with the extension, `-2`, `-3` added if it is taken.
     */
    public function sanitizeName(string $name): string
    {
        $name = Name::sanitize($name);
        $name = $this->extension === '' ? $name : $name . '.' . $this->extension;

        return basename(Name::unique($this->dirpath, $name));
    }

    /**
     * Remove the characters not allowed in a URL.
     *
     * @param string $value
     * @return string
     */
    public function sanitizeUrl(string $value): string
    {
        return (string) filter_var(trim($value), FILTER_SANITIZE_URL);
    }

    /**
     * Rename the entry on the disk and point the object to the new path.
     *
     * @param string $path Must be free.
     * @return static
     * @throws NotFound If the entry does not exist.
     * @throws OperationFailed
     */
    protected function relocate(string $path): static
    {
        $this->ensureExists();

        if (file_exists($path) || ! @rename($this->path, $path)) {
            throw new OperationFailed("Failed to move $this->path to $path");
        }
        $this->path = $path;

        return $this;
    }

    /**
     * Check that the entry exists before copying or moving it.
     *
     * @return void
     * @throws NotFound
     */
    protected function ensureExists(): void
    {
        if (! $this->exists) {
            throw new NotFound("$this->path does not exist");
        }
    }
}
