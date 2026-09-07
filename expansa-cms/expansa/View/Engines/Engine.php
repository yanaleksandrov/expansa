<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Factory;

abstract class Engine
{
    protected bool $shouldCache = false;

    protected string $cachePath = '';

    /**
     * The view that was last to be rendered.
     *
     * @var string
     */
    protected string $lastRendered = '';

    public function setCache(bool $shouldCache, string $cachePath): void
    {
        $this->cachePath   = $cachePath;
        $this->shouldCache = $shouldCache;
    }

    public function setFactory(Factory $factory): void
    {
    }

    /**
     * Render the view at $path with $data available to it.
     */
    abstract public function get(string $path, array $data = []): string;

    /**
     * Get the last view that was rendered.
     *
     * @return string
     */
    public function getLastRendered(): string
    {
        return $this->lastRendered;
    }
}
