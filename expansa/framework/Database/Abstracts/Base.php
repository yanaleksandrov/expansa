<?php

declare(strict_types=1);

namespace Expansa\Database\Abstracts;

abstract class Base
{
    protected function wrap(string $value): string
    {
        return sprintf('`%s`', $value);
    }
}
