<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Static access to Expansa\Codecs\Serializer.
 *
 * @method static string encode(mixed $value, bool $igbinary = false)
 * @method static mixed  decode(string $payload, bool|array $allowedClasses = false)
 */
class Serializer extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Codecs\Serializer';
    }
}
