<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Builders\Forms\Form as FormInstance;
use Expansa\Patterns\Facade;

/**
 * Provides a facade for managing form builder.
 *
 * @method static void         configure(array $fields)
 * @method static string       parse(array $fields)
 * @method static string       render(string $uid)
 * @method static string       enqueue(string $uid, array $attributes = [], array $fields = [])
 * @method static FormInstance override(string $uid, callable $function)
 */
class Form extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Builders\Form::class;
    }
}
