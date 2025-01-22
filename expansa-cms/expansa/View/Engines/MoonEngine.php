<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Compilers\MoonCompiler;
use Expansa\View\Factory;

class MoonEngine extends PhpEngine
{
    protected MoonCompiler $compiler;

    public function __construct()
    {
        $this->compiler = new MoonCompiler();
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
