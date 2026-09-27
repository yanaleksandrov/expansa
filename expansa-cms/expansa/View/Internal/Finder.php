<?php

declare(strict_types=1);

namespace Expansa\View\Internal;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Resolves view names to template files: `form/checkbox` in the view directories, `seo::panel` in the
 * directories of a namespace, an absolute path without extension as is. A directory is listed once,
 * a found or missing view is remembered.
 *
 * @internal
 * @package Expansa\View
 */
final class Finder
{
    /**
     * Directories of views without a namespace.
     *
     * @var string[]
     */
    public array $paths = [] {
        set {
            $this->paths = $value;
            $this->views = [];
        }
    }

    /**
     * Directories by namespace.
     *
     * @var array<string, string[]>
     */
    public array $namespaces = [] {
        set {
            $this->namespaces = $value;
            $this->views      = [];
        }
    }

    /**
     * Found templates by view name, `null` for a missing view.
     *
     * @var array<string, array{path: string, extension: string}|null>
     */
    private array $views = [];

    /**
     * Files of a directory by path relative to it, with forward slashes.
     *
     * @var array<string, array<string, string>>
     */
    private array $files = [];

    /**
     * @param string|string[] $paths      Directories of views without a namespace.
     * @param string[]        $extensions Template extensions, a view with several of them gets the first one.
     */
    public function __construct(string|array $paths = [], private array $extensions = [])
    {
        $this->paths = array_map($this->resolvePath(...), (array) $paths);
    }

    public function exists(string $view): bool
    {
        return $this->find($view) !== null;
    }

    /**
     * Get the template of the view.
     *
     * @param string $view
     * @return array{path: string, extension: string}|null
     */
    public function find(string $view): ?array
    {
        if (array_key_exists($view, $this->views)) {
            return $this->views[$view];
        }

        if (str_starts_with($view, '/') || ($view[1] ?? '') === ':') {
            return $this->views[$view] = $this->findFile($view);
        }

        if (str_contains($view, '::')) {
            [$namespace, $name] = explode('::', $view, 2);

            return $this->views[$view] = $this->findInPaths($name, $this->namespaces[$namespace] ?? []);
        }

        return $this->views[$view] = $this->findInPaths($view, $this->paths);
    }

    /**
     * Add a view directory and register its views at once: `prefix.sub.name`, with `namespace::` if given.
     *
     * @param string $path
     * @param string $prefix
     * @param string $namespace
     * @return static
     */
    public function addPath(string $path, string $prefix = '', string $namespace = ''): static
    {
        $path        = $this->resolvePath($path);
        $this->paths   = [...$this->paths, $path];

        $this->scanPath($path, $prefix, $namespace);

        return $this;
    }

    /**
     * Add a view directory searched before the others.
     *
     * @param string $path
     * @return static
     */
    public function prependPath(string $path): static
    {
        $this->paths = [$this->resolvePath($path), ...$this->paths];

        return $this;
    }

    /**
     * Add directories to a namespace, after its current ones or before them.
     *
     * @param string          $namespace
     * @param string|string[] $paths
     * @param bool            $prepend
     * @return static
     */
    public function addNamespace(string $namespace, string|array $paths, bool $prepend = false): static
    {
        $current = $this->namespaces[$namespace] ?? [];

        $this->namespaces = [
            ...$this->namespaces,
            $namespace => $prepend ? [...(array) $paths, ...$current] : [...$current, ...(array) $paths],
        ];

        return $this;
    }

    /**
     * Add directories to a namespace before its current ones.
     *
     * @param string          $namespace
     * @param string|string[] $paths
     * @return static
     */
    public function prependNamespace(string $namespace, string|array $paths): static
    {
        return $this->addNamespace($namespace, $paths, true);
    }

    /**
     * Replace the directories of a namespace.
     *
     * @param string          $namespace
     * @param string|string[] $paths
     * @return static
     */
    public function replaceNamespace(string $namespace, string|array $paths): static
    {
        $this->namespaces = [...$this->namespaces, $namespace => (array) $paths];

        return $this;
    }

    /**
     * Add an extension before the others, so it wins over a shorter one like `php`.
     *
     * @param string $extension
     * @return void
     */
    public function addExtension(string $extension): void
    {
        if (! in_array($extension, $this->extensions, true)) {
            $this->extensions = [$extension, ...$this->extensions];
            $this->views      = [];
        }
    }

    /**
     * @param string   $view
     * @param string[] $paths
     * @return array{path: string, extension: string}|null
     */
    private function findInPaths(string $view, array $paths): ?array
    {
        foreach ($paths as $path) {
            $files = $this->files[$path] ??= $this->listFiles($path);

            foreach ($this->extensions as $extension) {
                if (isset($files["$view.$extension"])) {
                    return ['path' => $files["$view.$extension"], 'extension' => $extension];
                }
            }
        }

        return null;
    }

    /**
     * @param string $path Absolute path without extension.
     * @return array{path: string, extension: string}|null
     */
    private function findFile(string $path): ?array
    {
        foreach ($this->extensions as $extension) {
            if (is_file("$path.$extension")) {
                return ['path' => realpath("$path.$extension"), 'extension' => $extension];
            }
        }

        return null;
    }

    /**
     * List the files of a directory and its subdirectories.
     *
     * @param string $directory
     * @return array<string, string> Real paths by path relative to the directory.
     */
    private function listFiles(string $directory): array
    {
        $directory = realpath($directory);
        if ($directory === false || ! is_dir($directory)) {
            return [];
        }

        $files  = [];
        $length = strlen($directory);

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $path = $file->getRealPath();

                $files[trim(strtr(substr($path, $length), '\\', '/'), '/')] = $path;
            }
        }

        return $files;
    }

    /**
     * Register the views of a directory and its subdirectories under dotted names.
     *
     * @param string $path
     * @param string $prefix
     * @param string $namespace
     * @return void
     */
    private function scanPath(string $path, string $prefix, string $namespace): void
    {
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $filename) {
            if (is_dir($path . '/' . $filename)) {
                $this->scanPath($path . '/' . $filename, ($prefix === '' ? '' : $prefix . '.') . $filename, $namespace);
                continue;
            }

            $extension = array_find($this->extensions, fn (string $extension) => str_ends_with($filename, '.' . $extension));
            if ($extension === null) {
                continue;
            }

            $view = ($prefix === '' ? '' : $prefix . '.') . basename($filename, '.' . $extension);
            if ($namespace !== '') {
                $view = $namespace . '::' . $view;
            }

            $this->views[$view] = ['path' => realpath($path . '/' . $filename), 'extension' => $extension];
        }
    }

    private function resolvePath(string $path): string
    {
        return realpath($path) ?: $path;
    }
}
