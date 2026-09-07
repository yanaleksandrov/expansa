<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Compilers\BladeCompiler;
use Expansa\View\Factory;

class BladeEngine extends PhpEngine
{
    public function __construct(
        protected readonly BladeCompiler $compiler = new BladeCompiler()
    ) {} // phpcs:ignore

    #[\Override]
    public function setFactory(Factory $factory): void
    {
        $this->compiler->setFactory($factory);
    }

    #[\Override]
    public function setCache(bool $shouldCache, string $cachePath): void
    {
        $this->compiler->setCachePath($cachePath);
        $this->compiler->setShouldCache($shouldCache);
    }

    #[\Override]
    public function get(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        if ($this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        return $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
    }
}
