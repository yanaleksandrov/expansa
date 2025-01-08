<?php

declare(strict_types=1);

namespace Expansa\Container;

class Container
{
    public function __construct(
        protected array $services = []
    ) {} // phpcs:ignore

    public function set($name, $service): void
    {
        $this->services[$name] = $service;
    }

    public function get($name): mixed
    {
        return $this->services[$name] ?? null;
    }
}
