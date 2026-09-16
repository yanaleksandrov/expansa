<?php

declare(strict_types=1);

namespace Expansa\Support;

use Expansa\Support\Exception\FinderException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait Finder
{
    /**
     * Recursively finds every .php file under $path, sorted.
     *
     * One directory read per level via RecursiveDirectoryIterator, instead of the two separate
     * glob() calls (files, then subdirectories) a manual recursive walk would need - and files
     * are appended straight into a single flat array as the iterator descends, instead of
     * array_merge()-ing a returned array back up through every level of recursion, which gets
     * quadratically slower as a tree gets deeper.
     *
     * @param string $path
     * @param int    $depth Maximum recursion depth: 0 scans only $path itself, 1 also scans its
     *                      immediate subdirectories, and so on.
     * @return array
     * @throws FinderException
     */
    public function discover(string $path, int $depth = 99): array
    {
        if (!is_dir($path)) {
            throw new FinderException("The path '$path' is not a directory");
        }

        // CURRENT_AS_PATHNAME skips allocating an SplFileInfo per entry - current() is just the
        // pathname string, cheap to check with str_ends_with() instead of getExtension().
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
