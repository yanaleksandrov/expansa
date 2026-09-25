<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Static access to Expansa\Codecs\Ldml.
 *
 * @method static string encode(string $format, bool $strict = false)
 * @method static string decode(string $format, bool $strict = false)
 */
class Ldml extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Codecs\Ldml';
    }
}
