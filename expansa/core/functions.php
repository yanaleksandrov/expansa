<?php

declare(strict_types=1);

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @return mixed
     */
    function tap(mixed $value, callable $callback = null): mixed
    {
        if (is_null($callback)) {
            return new Expansa\Support\TapProxy($value);
        }

        $callback($value);

        return $value;
    }
}