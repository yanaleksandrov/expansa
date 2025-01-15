<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Calypte\Traits;

use Exception;
use BadMethodCallException;

trait ForwardCalls
{
    /**
     * @throws Exception
     */
    protected function forwardCallTo(object $object, string $method, array $parameters): mixed
    {
        try {
            return $object->{$method}(...$parameters);
        } catch (Exception $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (
                ! preg_match($pattern, $e->getMessage(), $matches)
                ||
                $matches['class'] != get_class($object)
                ||
                $matches['method'] != $method
            ) {
                throw $e;
            }

            throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
        }
    }
}
