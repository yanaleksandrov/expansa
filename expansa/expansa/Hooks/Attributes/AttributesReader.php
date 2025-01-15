<?php

declare(strict_types=1);

namespace Expansa\Hooks\Attributes;

use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

trait AttributesReader
{
    /**
     * Retrieves the alias from the HookListenerAlias attribute.
     *
     * @param mixed $function The function or method to retrieve the attribute from.
     * @return string|null The alias name if the attribute is present, null otherwise.
     * @throws ReflectionException If the function or method does not exist.
     */
    protected function getAlias(mixed $function): ?string
    {
        $attributes = $this->getFunction($function)->getAttributes(HookListenerAlias::class);

        if (!empty($attributes)) {
            return (string) $attributes[0]->newInstance()->anonymousFunctionName;
        }
        return null;
    }

    /**
     * Retrieves the priority from the HookListenerPriority attribute.
     *
     * @param mixed $function The function or method to retrieve the attribute from.
     * @return int|null The priority value if the attribute is present, null otherwise.
     * @throws ReflectionException If the function or method does not exist.
     */
    protected function getProperty(mixed $function): ?int
    {
        $attributes = $this->getFunction($function)->getAttributes(HookListenerPriority::class);

        if (!empty($attributes)) {
            return (int) $attributes[0]->newInstance()->priority;
        }
        return null;
    }

    /**
     * Creates a ReflectionMethod or ReflectionFunction instance based on the provided function.
     *
     * @param mixed $function The function to reflect. It can be a callable or an array representing a class method.
     * @return ReflectionMethod|ReflectionFunction A Reflection instance representing the provided function.
     * @throws ReflectionException If the function or method does not exist.
     */
    protected function getFunction(mixed $function): ReflectionMethod|ReflectionFunction
    {
        return is_array($function)
            ? new ReflectionMethod($function[0], $function[1])
            : new ReflectionFunction($function);
    }
}
