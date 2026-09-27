<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

use Expansa\Filesystem\Internal\AbstractEntry;
use InvalidArgumentException;
use Expansa\Filesystem\Contracts\File as FileContract;
use Expansa\Filesystem\Exceptions\OperationFailed;
use Expansa\Filesystem\Internal\Name;

/**
 * A file: reading, writing, copying, moving and sending it to the browser.
 * Failed operations throw OperationFailed.
 *
 * @package Expansa\Filesystem
 */
final class File extends AbstractEntry implements FileContract
{
    public bool $exists {
        get => is_file($this->path);
    }

    public int $bytes {
        get => $this->exists ? (int) filesize($this->path) : 0;
    }

    /**
     * MD5 of the contents, empty for a missing file.
     */
    public string $hash {
        get => $this->exists ? (string) hash_file('md5', $this->path) : '';
    }

    /**
     * MIME type detected from the contents, empty for a missing file.
     */
    public string $mime {
        get => $this->exists ? (string) mime_content_type($this->path) : '';
    }

    public function chmod(int $mode = 0644): static
    {
        if (! @chmod($this->path, $mode)) {
            throw new OperationFailed("Failed to change the permissions of $this->path");
        }

        return $this;
    }

    public function clean(): static
    {
        return $this->write('', append: false);
    }

    public function copy(string $name): static
    {
        $this->ensureExists();

        $path = $this->dirpath . '/' . $this->basenameOf($name);
        if (file_exists($path)) {
            throw new OperationFailed("File $path already exists");
        }

        if (! @copy($this->path, $path)) {
            throw new OperationFailed("Failed to copy $this->path to $path");
        }

        return new self($path);
    }

    public function delete(): bool
    {
        return $this->exists && @unlink($this->path);
    }

    public function download(): void
    {
        if (! $this->exists) {
            return;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $this->basename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $this->bytes);

        if (ob_get_level() > 0) {
            ob_clean();
        }
        flush();
        readfile($this->path);
        exit;
    }

    public function move(string $directory): static
    {
        $directory = rtrim($directory, '/\\');
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
            throw new OperationFailed("Failed to create the directory $directory");
        }

        return $this->relocate(Name::unique($directory, $this->basename));
    }

    public function read(): string
    {
        return $this->exists ? (string) file_get_contents($this->path) : '';
    }

    public function rename(string $name): static
    {
        $name = Name::sanitize($name);
        if ($name === '') {
            throw new InvalidArgumentException('The file name is empty or contains only invalid characters');
        }

        return $this->relocate(Name::unique($this->dirpath, $this->basenameOf($name)));
    }

    public function replace(array $pairs): static
    {
        return $this->write(strtr($this->read(), $pairs), append: false);
    }

    public function touch(?int $time = null, ?int $atime = null): static
    {
        $time ??= time();

        if (! @touch($this->path, $time, $atime ?? $time)) {
            throw new OperationFailed("Failed to update the timestamps of $this->path");
        }
        clearstatcache(true, $this->path);

        return $this;
    }

    public function write(string $content, bool $append = true): static
    {
        $directory = $this->dirpath;
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
            throw new OperationFailed("Failed to create the directory $directory");
        }

        if (@file_put_contents($this->path, $content, $append ? FILE_APPEND : 0) === false) {
            throw new OperationFailed("Unable to write to the file $this->path");
        }
        clearstatcache(true, $this->path);

        return $this;
    }


    /**
     * Append the extension of this file to a name.
     *
     * @param string $name
     * @return string
     */
    private function basenameOf(string $name): string
    {
        return $this->extension === '' ? $name : $name . '.' . $this->extension;
    }
}
