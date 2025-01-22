<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Compilers\BladeCompiler;
use Expansa\View\Factory;

class BladeEngine extends PhpEngine
{
    protected BladeCompiler $compiler;

    public function __construct()
    {
        $this->compiler = new BladeCompiler();
    }

    public function setFactory(Factory $factory): void
    {
        $this->compiler->setFactory($factory);
    }

    public function setCache(bool $shouldCache, string $cachePath): void
    {
        $this->compiler->setCachePath($cachePath);
        $this->compiler->setShouldCache($shouldCache);
    }

    public function get(string $path, array $data = []): string
    {
        if ($this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        return $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
    }
}
