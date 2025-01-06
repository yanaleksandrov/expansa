<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;
use Expansa\Security\Validator as SecurityValidator;

/**
 * @method static SecurityValidator data(array $fields, array $rules, bool $break = false)
 */
class Validator extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Security\Validator';
    }
}
