<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

use FilesystemIterator;
use SplFileInfo;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Expansa\Filesystem\Contracts\Directory as DirectoryContract;
use Expansa\Filesystem\Exceptions\OperationFailed;

/**
 * A directory: listing, copying, moving and deleting it with its contents.
 * Failed operations throw OperationFailed.
 *
 * @package Expansa\Filesystem
 */
final class Directory extends AbstractEntry implements DirectoryContract
{
    public bool $exists {
        get => is_dir($this->path);
    }

    public int $bytes {
        get {
            $bytes = 0;
            foreach ($this->iterate() as $item) {
                $bytes += $item->isFile() ? $item->getSize() : 0;
            }

            return $bytes;
        }
    }

    public function directories(int $depth = 0): array
    {
        $folders = $this->glob($this->path, '*', GLOB_ONLYDIR, $depth);

        sort($folders);

        return $folders;
    }

    public function tree(): array
    {
        $search = function (string $path) use (&$search): array {
            $tree = [];
            foreach (glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [] as $item) {
                $tree[basename($item)] = $search($item);
            }
            ksort($tree);

            return $tree;
        };

        return [$this->basename => $search($this->path)];
    }

    public function files(string $pattern = '*', int $depth = 0): array
    {
        $files = $this->glob($this->path, $pattern, GLOB_BRACE | GLOB_MARK, $depth);

        sort($files);

        return $files;
    }

    public function create(int $mode = 0755): static
    {
        if (! is_dir($this->path) && ! @mkdir($this->path, $mode, true) && ! is_dir($this->path)) {
            throw new OperationFailed("Failed to create the directory $this->path");
        }

        return $this;
    }

    public function chmod(int $mode = 0755, bool $recursive = false): static
    {
        $paths = [$this->path => $mode];
        if ($recursive) {
            foreach ($this->iterate(RecursiveIteratorIterator::SELF_FIRST) as $item) {
                $paths[$item->getPathname()] = $item->isDir() ? $mode : 0644;
            }
        }

        foreach ($paths as $path => $pathMode) {
            if (! @chmod($path, $pathMode)) {
                throw new OperationFailed("Failed to change the permissions of $path");
            }
        }

        return $this;
    }

    public function clean(): static
    {
        foreach ($this->iterate(RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            $path    = $item->getPathname();
            $deleted = $item->isDir() && ! $item->isLink() ? @rmdir($path) : @unlink($path);
            if (! $deleted) {
                throw new OperationFailed("Failed to delete $path");
            }
        }

        return $this;
    }

    public function copy(string $name): static
    {
        $to = $this->dirpath . '/' . $name;
        if (file_exists($to)) {
            throw new OperationFailed("Directory $to already exists");
        }

        new self($to)->create();

        $offset = strlen($this->path);
        foreach ($this->iterate(RecursiveIteratorIterator::SELF_FIRST) as $item) {
            $target = $to . substr($item->getPathname(), $offset);
            $copied = $item->isDir() ? @mkdir($target, 0755) : @copy($item->getPathname(), $target);
            if (! $copied) {
                throw new OperationFailed("Failed to copy {$item->getPathname()} to $target");
            }
        }

        return new self($to);
    }

    public function delete(): bool
    {
        if (! $this->exists) {
            return false;
        }

        try {
            $this->clean();
        } catch (OperationFailed) {
            return false;
        }

        return @rmdir($this->path);
    }

    public function download(): void
    {
        if (! $this->exists || ! class_exists(ZipArchive::class)) {
            return;
        }

        $archive = $this->path . '.zip';
        $zip     = new ZipArchive();
        if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return;
        }

        $offset = strlen($this->path) + 1;
        foreach ($this->iterate() as $item) {
            if ($item->isFile()) {
                $zip->addFile($item->getPathname(), substr($item->getPathname(), $offset));
            }
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($archive) . '"');
        header('Content-Length: ' . filesize($archive));

        readfile($archive);
        unlink($archive);
        exit;
    }

    public function move(string $directory): static
    {
        $directory = rtrim($directory, '/\\');
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true)) {
            throw new OperationFailed("Failed to create the directory $directory");
        }

        return $this->relocate($directory . '/' . $this->basename);
    }

    public function rename(string $name): static
    {
        return $this->relocate($this->dirpath . '/' . $name);
    }


    /**
     * Iterate over the contents recursively, an empty list for a missing directory.
     *
     * @param int $mode RecursiveIteratorIterator mode.
     * @return iterable<SplFileInfo>
     */
    private function iterate(int $mode = RecursiveIteratorIterator::LEAVES_ONLY): iterable
    {
        if (! $this->exists) {
            return [];
        }

        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    /**
     * Glob a directory and its subdirectories down to a depth.
     *
     * @param string $path
     * @param string $pattern
     * @param int    $flags
     * @param int    $depth
     * @return string[]
     */
    private function glob(string $path, string $pattern, int $flags, int $depth): array
    {
        $found = glob($path . '/' . $pattern, $flags | GLOB_NOSORT) ?: [];

        if ($depth > 0) {
            foreach (glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [] as $folder) {
                array_push($found, ...$this->glob($folder, $pattern, $flags, $depth - 1));
            }
        }

        return $found;
    }
}
