<?php

declare(strict_types=1);

namespace Expansa\View;

use Expansa\View\Engines\Engine;
use Expansa\View\Engines\EngineManager;
use Expansa\View\Exception\ViewException;

class Factory
{
    protected array $shared = [];

    protected bool $cache = false;

    protected string $cachePath = '';

    protected static array $sectionContents = [];

    protected static array $sectionStack = [];

    public function __construct(
        protected Finder $finder,
        protected EngineManager $engine,
        array $config
    )
    {
        $this->cache = is_bool($config['cache']) && $config['cache'];

        if (isset($config['cache_path'])) {
            if (!is_dir($config['cache_path'])) {
                mkdir($config['cache_path'], 0755, true);
            }
            $this->cachePath = $config['cache_path'];
        }

        $this->share('__env', $this);
    }

    public function make(string $view, array $data = []): View
    {
        $file = $this->finder->find($view);

        if (is_null($file)) {
            throw new ViewException("File '$view' does not exist!");
        }

        $data = array_merge($this->shared, $data);

        return new View($this, $this->getEngine($file), $view, $file['path'], $data);
    }

    public function getEngine(array $file): Engine
    {
        $engine = $this->engine->resolveByExtension($file['extension']);

        $engine->setFactory($this);
        $engine->setCache($this->cache, $this->cachePath);

        return $engine;
    }

    public function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }
    }

    public function getShare(string $key, mixed $default = null): mixed
    {
        return $this->shared[$key] ?? $default;
    }

    public function getShared(): array
    {
        return $this->shared;
    }

    public function exists(string $view): bool
    {
        return $this->finder->exists($view);
    }

    public function startSection(string $name, string $content = null): void
    {
        if (is_null($content)) {
            ob_start();
            static::$sectionStack[] = $name;
        } else {
            $this->extendSection($name, $content);
        }
    }

    public function extendSection(string $name, string $content): void
    {
        if (isset(static::$sectionContents[$name])) {
            $content = str_replace('@parent', $content, static::$sectionContents[$name]);
        }
        static::$sectionContents[$name] = $content;
    }

    public function stopSection($overwrite = false): string
    {
        if (empty(static::$sectionStack)) {
            throw new \InvalidArgumentException('Cannot end a section without first starting one.');
        }

        $name = array_pop(static::$sectionStack);

        if ($overwrite) {
            static::$sectionContents[$name] = ob_get_clean();
        } else {
            $this->extendSection($name, ob_get_clean());
        }

        return $name;
    }

    public function yieldSection()
    {
        if (empty(static::$sectionStack)) {
            return '';
        }
        $name = $this->stopSection();

        return $this->yieldContent($name);
    }

    public function yieldContent($name)
    {
        return static::$sectionContents[$name] ?? '';
    }

    public function addNamespace(string $namespace, string|array $paths, bool $prepend = false): static
    {
        $this->finder->addNamespace($namespace, $paths, $prepend);

        return $this;
    }
}
