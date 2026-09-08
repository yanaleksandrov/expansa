<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Closure;
use InvalidArgumentException;

class EngineManager
{
    protected array $extensions = [
        'blade.php' => 'blade',
        'php'       => 'php',
        'html'      => 'file',
        'css'       => 'file',
        'js'        => 'js',
    ];

    protected array $resolvers = [];

    protected array $resolved = [];

    public function __construct()
    {
        $this->register('file', fn () => new FileEngine());
        $this->register('php', fn () => new PhpEngine());
        $this->register('blade', fn () => new BladeEngine());
        $this->register('js', fn () => new JsEngine());
    }

    public function register(string $name, Closure $resolver): void
    {
        unset($this->resolved[$name]);

        $this->resolvers[$name] = $resolver;
    }

    public function resolveByExtension(string $extension): Engine
    {
        if (isset($this->extensions[$extension])) {
            return $this->resolve($this->extensions[$extension]);
        }

        throw new InvalidArgumentException("Engine with extension [$extension] not found.");
    }

    public function resolve(string $name): Engine
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (isset($this->resolvers[$name])) {
            return $this->resolved[$name] = ($this->resolvers[$name])();
        }

        throw new InvalidArgumentException("Engine [$name] not found.");
    }
}
