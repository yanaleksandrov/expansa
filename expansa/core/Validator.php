<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;
use Expansa\Security\Validator as SecurityValidator;

/**
 * Validator Facade class.
 *
 * This class provides a static interface to the SecurityValidator instance.
 *
 * @method static SecurityValidator data(array $fields, array $rules, bool $break = false) Validate data against rules.
 */
class Validator extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Security\Validator';
    }
}
