<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Schema;

class Expression
{
    public function __construct(
        protected mixed $value
    ) {} // phpcs:ignore

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
