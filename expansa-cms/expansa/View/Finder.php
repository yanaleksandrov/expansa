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

    protected array $extensions = ['blade.php', 'php', 'html', 'css', 'scss', 'js'];

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
            list($ns, $name) = explode("::", $view);

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
        $path  = $this->resolvePath($path);
        $files = $this->getAllFiles($path);

        foreach ($files as $file) {
            $name = trim(str_replace($path, '', $file), '/');
            if (! in_array($name, $names)) {
                continue;
            }

            $extension = substr($name, strlen($view) + 1);

            return [
                'path'      => $file,
                'name'      => (empty($prefix) ? '' : $prefix . '.') . basename($file, '.' . $extension),
                'extension' => $extension,
            ];
        }

        return null;
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

    // Work with namespace
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

            if ($file = $this->resolveFilename($filename)) {
                $view = $prefix . (empty($prefix) ? '' : '.') . $file['name'];

                if (! empty($namespace)) {
                    $view = $namespace . '::' . $view;
                }

                $this->cache[ $view ] = array_merge($file, [
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
        foreach ($this->extensions as $ext) {
            if (str_ends_with($path, '.' . $ext)) {
                return [
                    'name'      => basename($path, '.' . $ext),
                    'extension' => $ext,
                ];
            }
        }

        return null;
    }
}
