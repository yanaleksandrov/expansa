<?php

declare(strict_types=1);

namespace Expansa\Support\Traits;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;

trait Macroable
{
    protected static array $macros = [];

    public static function macro($name, $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro($name): bool
    {
        return isset(static::$macros[$name]);
    }

    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    /**
     * @throws ReflectionException
     */
    public static function mixin($mixin, $replace = true): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || ! static::hasMacro($method->name)) {
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    public static function __callStatic($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.',
                static::class,
                $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    public function __call($method, $parameters)
    {
        if (! static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.',
                static::class,
                $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}
