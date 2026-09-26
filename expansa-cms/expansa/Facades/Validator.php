<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;
use Expansa\Security\Validator as BaseValidator;

/**
 * Static access to the validator: `Validator::data($fields, $rules)->apply()`.
 *
 * @method static void          configure(?Closure $translate = null)
 * @method static BaseValidator data(array $fields, array $rules, bool $break = false)
 * @method static BaseValidator apply()
 * @method static bool          isValid()
 * @method static BaseValidator extend(string $type, string $message, ?callable $callback = null)
 */
class Validator extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return BaseValidator::class;
    }
}
