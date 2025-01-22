<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

class EngineManager
{
    protected array $extensions = [
        'blade.php' => 'moon',
        'php'       => 'php',
        'html'      => 'file',
        'css'       => 'file',
        'scss'      => 'scss',
        'js'        => 'js',
    ];

    protected array $resolvers = [];

    protected array $resolved = [];

    public function __construct()
    {
        $this->register('file', fn() => new FileEngine());
        $this->register('php', fn() => new PhpEngine());
        $this->register('moon', fn() => new MoonEngine());
        $this->register('scss', fn() => new ScssEngine());
        $this->register('js', fn() => new JsEngine());
    }

    public function register($name, \Closure $resolver): void
    {
        unset($this->resolved[$name]);

        $this->resolvers[$name] = $resolver;
    }

    public function resolveByExtension($extension): Engine
    {
        if (isset($this->extensions[$extension])) {
            return $this->resolve($this->extensions[$extension]);
        }

        throw new \InvalidArgumentException("Engine with extension [$extension] not found.");
    }

    public function resolve($name)
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (isset($this->resolvers[$name])) {
            return $this->resolved[$name] = call_user_func($this->resolvers[$name]);
        }

        throw new \InvalidArgumentException("Engine [$name] not found.");
    }
}
