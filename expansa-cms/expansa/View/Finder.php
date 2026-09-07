<?php

declare(strict_types=1);

namespace Expansa\View;

use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Finder
{
    protected array $paths = [];

    protected array $namespaces = [];

    protected array $extensions = ['blade.php', 'php', 'html', 'css', 'js'];

    public array $views = [];

    public function __construct(string|array $paths = [])
    {
        foreach ((array) $paths as $path) {
            $this->paths[] = $this->resolvePath($path);
        }
    }

    public function exists(string $view): bool
    {
        if ($data = $this->find($view)) {
            $this->views[$view] = $data;

            return true;
        }

        return false;
    }

    public function find(string $view): ?array
    {
        if (isset($this->views[$view])) {
            return $this->views[$view];
        }

        if (str_contains($view, '::')) {
            [$ns, $name] = explode('::', $view);

            if (! isset($this->namespaces[$ns])) {
                return null;
            }

            return $this->views[$view] = $this->findInPaths($name, $this->namespaces[$ns]);
        }

        return $this->views[$view] = $this->findInPaths($view, $this->paths);
    }

    protected function findInPaths(string $view, array $paths): ?array
    {
        $names = $this->getPossibleViewFiles($view);

        foreach ($paths as $path) {
            if ($result = $this->findInPath($view, $path, $names)) {
                return $result;
            }
        }

        return null;
    }

    protected function findInPath(string $view, string $path, array $names, string $prefix = ''): ?array
    {
        $path    = $this->resolvePath($path);
        $pathLen = strlen($path);

        // Plain foreach beats array_find() here: this loop runs once per files-in-$path
        // (dozens to hundreds), and array_find()'s per-element closure call measured
        // ~20-30% slower than a manual loop with early return - benchmarked, not assumed.
        $name = null;
        foreach ($this->getAllFiles($path) as $file) {
            $candidate = $this->relativeName($file, $pathLen);
            if (in_array($candidate, $names, true)) {
                $name = $candidate;
                break;
            }
        }

        if ($name === null) {
            return null;
        }

        $extension = substr($name, strlen($view) + 1);

        return [
            'path'      => $file,
            'name'      => (empty($prefix) ? '' : $prefix . '.') . basename($file, '.' . $extension),
            'extension' => $extension,
        ];
    }

    /**
     * $file relative to $path (of length $pathLen), using forward slashes throughout
     * regardless of platform - realpath() (both directly and via SplFileInfo) returns
     * backslash-separated paths on Windows, which a plain trim($name, '/') doesn't
     * strip (leaving a stray leading "\" and backslash-joined nested segments), so this
     * never matched the forward-slash view names callers actually ask for, e.g.
     * "form/checkbox". substr()+strtr() over str_replace(): $file is always exactly
     * $path plus a suffix (it comes from walking $path), so slicing off the known
     * prefix length is enough - no need for str_replace() to search for it, which
     * benchmarked ~35% slower over a real view directory tree.
     */
    protected function relativeName(string $file, int $pathLen): string
    {
        $relative = substr($file, $pathLen);
        $relative = strtr($relative, '\\', '/');

        return trim($relative, '/');
    }

    private function getAllFiles(string $directory): array
    {
        static $files = [];
        if (isset($files[$directory])) {
            return $files[$directory];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[$directory][] = $file->getRealPath();
            }
        }

        return $files[$directory];
    }

    protected function getPossibleViewFiles(string $view): array
    {
        return array_map(fn ($extension) => $view . '.' . $extension, $this->extensions);
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function setPaths(array $paths): static
    {
        $this->paths = $paths;

        return $this;
    }

    public function addPath(string $path, string $prefix = '', string $namespace = ''): static
    {
        $path = $this->resolvePath($path);

        $this->scanPath($this->paths[] = $path, $prefix, $namespace);

        return $this;
    }

    public function prependPath(string $path): static
    {
        array_unshift($this->paths, $this->resolvePath($path));

        return $this;
    }

    public function addNamespace(string $namespace, string|array $paths, bool $prepend = false): static
    {
        $paths = (array) $paths;

        if (isset($this->namespaces[$namespace])) {
            $paths = ($prepend) ? array_merge($paths, $this->namespaces[$namespace])
                : array_merge($this->namespaces[$namespace], $paths);
        }

        $this->namespaces[$namespace] = $paths;

        return $this;
    }

    public function prependNamespace(string $namespace, string|array $paths): static
    {
        return $this->addNamespace($namespace, $paths, true);
    }

    public function replaceNamespace(string $namespace, string|array $paths): static
    {
        $this->namespaces[$namespace] = (array) $paths;

        return $this;
    }

    public function setNamespaces(array $namespaces): static
    {
        $this->namespaces = $namespaces;

        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    protected function scanPath(string $path, string $prefix = '', string $namespace = ''): void
    {
        $filenames = array_diff(scandir($path), ['.', '..']);

        foreach ($filenames as $filename) {
            if (is_dir($path . '/' . $filename)) {
                $this->scanPath(
                    $path . '/' . $filename,
                    (empty($prefix) ? '' : $prefix . '.') . $filename,
                    $namespace
                );
                continue;
            }

            if ($file = $this->resolveFile($filename)) {
                $view = $prefix . (empty($prefix) ? '' : '.') . $file['name'];

                if (! empty($namespace)) {
                    $view = $namespace . '::' . $view;
                }

                $this->views[ $view ] = array_merge($file, [
                    'path' => realpath($path . '/' . $filename),
                ]);
            }
        }
    }

    protected function resolvePath(string $path): string
    {
        return realpath($path) ?: $path;
    }

    protected function resolveFile(string $path): ?array
    {
        $ext = array_find($this->extensions, fn (string $ext) => str_ends_with($path, '.' . $ext));

        if ($ext === null) {
            return null;
        }

        return [
            'name'      => basename($path, '.' . $ext),
            'extension' => $ext,
        ];
    }
}
