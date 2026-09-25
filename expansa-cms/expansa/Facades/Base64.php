<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Static access to Expansa\Codecs\Base64.
 *
 * @method static string      encode(string $data, bool $url = false)
 * @method static string|null decode(string $data)
 */
class Base64 extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Codecs\Base64::class;
    }
}
