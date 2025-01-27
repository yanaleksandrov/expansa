<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Expansa\Filesystem\Contracts\CommonInterface;
use Expansa\Filesystem\Contracts\DirectoryInterface;

/**
 * Class Directory.
 *
 * A class that represents a directory on a file system.
 */
class Directory extends EntryHandler implements CommonInterface, DirectoryInterface
{
    public function read(int $depth = 0, bool $treeFormat = false): array
    {
        if ($treeFormat) {
            $search = function (string $path) use (&$search): array {
                $path  = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $items = glob($path . '*', GLOB_NOSORT);

                $tree = [];
                foreach ($items as $item) {
                    if (is_dir($item)) {
                        $tree[basename($item)] = $search($item);
                    }
                }
                return $tree;
            };

            $findFolders = $search($this->path);

            ksort($findFolders);

            return [ basename($this->path) => $findFolders ];
        }

        $search = function (string $path, int $current_depth) use (&$search, $depth) {
            $flags   = GLOB_ONLYDIR | GLOB_NOSORT | GLOB_ERR;
            $folders = glob($this->normalizePath($path . '/*'), $flags);

            if ($current_depth < $depth) {
                $subfolders = glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT);

                foreach ($subfolders as $folder) {
                    $folders = array_merge($folders, $search($folder, $current_depth + 1));
                }
            }

            return $folders;
        };

        $folders = $search($this->path, 0);

        sort($folders);

        return $folders;
    }

    public function files(string $pattern = '*', int $depth = 0): array
    {
        $search = function ($path, int $current_depth) use (&$search, $pattern, $depth) {
            $flags = GLOB_BRACE | GLOB_NOSORT | GLOB_MARK | GLOB_ERR;
            $files = glob($this->normalizePath($path . '/' . $pattern), $flags);

            if ($current_depth < $depth) {
                $folders = glob($path . '/*', GLOB_ONLYDIR | GLOB_NOSORT);

                foreach ($folders as $folder) {
                    $files = array_merge($files, $search($folder, $current_depth + 1));
                }
            }

            return $files ?: [];
        };

        $files = $search($this->path, 0);

        sort($files);

        return $files;
    }

    public function make(int $mode = 0755): Directory
    {
        if (! is_dir($this->path)) {
            mkdir($this->path, $mode, true);
        }
        return $this;
    }

    public function chmod(int $mode = 0755, bool $recursive = false): Directory
    {
        chmod($this->path, $mode);

        if ($recursive) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->path),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                if ($file->isDir()) {
                    chmod($file->getRealPath(), $mode);
                } else {
                    chmod($file->getRealPath(), 0644);
                }
            }
        }

        return $this;
    }

    public function clean(string $path = ''): Directory
    {
        $path = $path ?: $this->path;
        if (is_dir($path)) {
            $paths = glob($path . '/*');

            foreach ($paths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
                if (is_dir($path)) {
                    $this->clean($path);
                    rmdir($path);
                }
            }
        }
        return $this;
    }

    public function copy(string $name): Directory
    {
        if (!is_dir($this->path)) {
            return $this;
        }

        $to = $this->dirpath . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($to)) {
            mkdir($to, 0755, true);
        }

        foreach (scandir($this->path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fromPath = $this->path . DIRECTORY_SEPARATOR . $item;
            $toPath   = $to . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fromPath)) {
                (new self($fromPath))->copy($toPath);
            } else {
                copy($fromPath, $toPath);
            }
        }

        return $this;
    }

    public function delete(): bool
    {
        $this->clean();
        if (rmdir($this->path)) {
            return true;
        }
        return false;
    }

    public function download(): void
    {
        if (class_exists('ZipArchive')) {
            $zipFilepath = sprintf('%s.zip', $this->path);

            $zip = new ZipArchive();
            if ($zip->open($zipFilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->path),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if ($file->isDir()) {
                        continue;
                    }

                    $filepath     = $file->getRealPath();
                    $relativePath = substr($filepath, strlen($this->path) + 1);

                    $zip->addFile($filepath, $relativePath);
                }

                $zip->close();
            }

            if (file_exists($zipFilepath)) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($zipFilepath) . '"');
                header('Content-Length: ' . filesize($zipFilepath));

                readfile($zipFilepath);

                // remove archive after download
                unlink($zipFilepath);
                exit;
            }
        }
    }

    public function move(string $to): Directory
    {
        // TODO: Implement move() method.
    }

    public function rename(string $name): Directory
    {
        if (is_dir($this->path)) {
            $dirpath = sprintf('%s%s%s', dirname($this->path), DIRECTORY_SEPARATOR, $name);
            if (! is_dir($dirpath) && rename($this->path, $dirpath)) {
                $this->path = $dirpath;
            }
        }
        return $this;
    }

    public function get(string $path): Directory
    {
        return new self($path);
    }
}
