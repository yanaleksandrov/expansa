<?php

declare(strict_types=1);

namespace Expansa\Support\Traits;

use Expansa\Support\Exceptions\SupportException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Finds the PHP files of a directory tree.
 *
 * @package Expansa\Support\Traits
 */
trait FindsFiles
{
    /**
     * Recursively find every .php file under a directory, sorted.
     * One directory read per level, files go straight into one flat array.
     *
     * @param string $path
     * @param int    $depth Maximum recursion depth: 0 scans only $path itself, 1 also scans its
     *                      immediate subdirectories, and so on.
     * @return string[]
     * @throws SupportException If the path is not a directory.
     */
    public function discover(string $path, int $depth = 99): array
    {
        if (!is_dir($path)) {
            throw new SupportException("The path '$path' is not a directory");
        }

        // CURRENT_AS_PATHNAME: a path string instead of an SplFileInfo per entry
        $flags = FilesystemIterator::SKIP_DOTS
            | FilesystemIterator::UNIX_PATHS
            | FilesystemIterator::CURRENT_AS_PATHNAME
            | FilesystemIterator::KEY_AS_PATHNAME;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, $flags),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $iterator->setMaxDepth($depth);

        $files = [];
        foreach ($iterator as $file) {
            if (str_ends_with($file, '.php')) {
                $files[] = $file;
            }
        }

        sort($files);

        return $files;
    }
}
