<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;

/**
 * Provides a facade for managing form builder.
 *
 * @method static void   configure(array $fields)
 * @method static string parse(array $fields)
 * @method static string make(string $uid, array $attributes = [], array $fields = [])
 * @method static string enqueue(string $uid, array $attributes = [], array $fields = [])
 */
class Form extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Builders\Form';
    }
}
