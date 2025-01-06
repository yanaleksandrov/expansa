<?php

declare(strict_types=1);

namespace Expansa\Support;

class TapProxy
{
    /**
     * Create a new tap proxy instance.
     *
     * @param  mixed  $target
     * @return void
     */
    public function __construct(
        protected mixed $target
    ) {} // phpcs:ignore

    /**
     * Dynamically pass method calls to the target.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
