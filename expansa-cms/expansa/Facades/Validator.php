<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;
use Expansa\Security\Validator as BaseValidator;

/**
 * This class provides a static interface to the Validator instance.
 *
 * @method static BaseValidator data(array $fields, array $rules, bool $break = false) Validate data against rules.
 * @method static BaseValidator extend(string $type, string $message, ?callable $callback = null) Extend validator.
 */
class Validator extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Security\Validator';
    }
}
