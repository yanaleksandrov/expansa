<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;

trait AttributesReader
{
    /**
     * @throws ReflectionException
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
     * @throws ReflectionException
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
     * @throws ReflectionException
     */
    protected function getFunction(mixed $function): ReflectionMethod|ReflectionFunction
    {
        return is_array($function)
            ? new ReflectionMethod($function[0], $function[1])
            : new ReflectionFunction($function);
    }
}
