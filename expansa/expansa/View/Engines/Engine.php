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
    protected $lastRendered;

    public function setCache(bool $shouldCache, string $cachePath): void
    {
        $this->cachePath = $cachePath;

        $this->shouldCache = $shouldCache;
    }

    public function setFactory(Factory $factory): void
    {
    }

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
