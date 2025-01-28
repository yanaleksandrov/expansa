<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Patterns\Facade;

/**
 * Csv Facade class provides static methods for CSV encoding and decoding.
 *
 * @method static array  decode($filepathOrData, string $delimiter = 'auto', string $enclosure = 'auto', string $linebreak = 'auto')
 * @method static string encode($items, string $delimiter = ',', string $enclosure = '"', string $linebreak = "\r\n")
 */
class Csv extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Codec\Csv';
    }
}
