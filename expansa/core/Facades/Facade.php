<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Facades\Exception\FacadeException;

/**
 * The Facade Class.
 *
 * A simple package that convert a service class into a static-like class.
 */
class Facade
{
    /**
     * Store all the resolved service class instance.
     *
     * @var array
     */
    protected static array $class = [];

    /**
     * Store the resolved service class instance that will be use later.
     *
     * @param string $classNamespace
     * @param  mixed $classInstance
     * @return void
     */
    protected static function setClass(string $classNamespace, mixed $classInstance): void
    {
        self::$class[$classNamespace] = $classInstance;
    }

    /**
     * Get the resolved service class instance of the given class namespace.
     *
     * @param string $classNamespace
     * @return mixed
     */
    protected static function getClass(string $classNamespace): mixed
    {
        return self::$class[$classNamespace] ?? false;
    }

    /**
     * Handle all the methods that will lead to service class capability.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws FacadeException
     */
    public static function __callStatic(string $method, array $args)
    {
        return (self::getResolvedClassInstance())->{$method}(...$args);
    }

    /**
     * Check if the class namespace already have a cached resolved instance,
     * if not then the class namespace must be resolved.
     *
     * @return mixed
     * @throws FacadeException
     */
    protected static function getResolvedClassInstance(): mixed
    {
        $classNamespace = static::getStaticClassAccessor();

        return self::getClass($classNamespace) ?: self::resolveClassNamespace($classNamespace);
    }

    /**
     * Resolver for service class namespace.
     * Set the resolved service class instance to the class property.
     *
     * @param string $classNamespace
     * @return mixed
     * @throws FacadeException
     */
    protected static function resolveClassNameSpace(mixed $classNamespace): mixed
    {
        if (!is_string($classNamespace)) {
            throw new FacadeException('The given class namespace value is not a string and can not be resolved.');
        }

        if (!class_exists($classNamespace)) {
            throw new FacadeException('The class namespace is not exist and can not be resolved.');
        }

        $classNamespace = self::classNamespaceDecorator($classNamespace);

        $classInstance = new $classNamespace();

        self::setClass($classNamespace, $classInstance);

        return $classInstance;
    }

    /**
     * This method decorate the class namespace, adding a default backslash
     * in the first character to avoid the namespace scope issue.
     *
     * @param string $classNamespace
     * @return string
     */
    protected static function classNamespaceDecorator(string $classNamespace): string
    {
        if ($classNamespace[0] !== '\\') {
            return (string) substr_replace($classNamespace, '\\', 0, 0);
        }

        return $classNamespace;
    }

    /**
     * Get the service class namespace that will be converted into static class.
     *
     * @return string
     * @throws FacadeException
     */
    protected static function getStaticClassAccessor(): string
    {
        throw new FacadeException('The "getStaticClassAccessor()" method is not declared by the successor class.');
    }
}
