<?php

declare(strict_types=1);

namespace Expansa\Database\Abstracts;

use Expansa\Database\Schema\Expression;

abstract class Base
{
    protected function wrap(string|Expression $value): string
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }
        return sprintf('`%s`', $value);
    }
}
