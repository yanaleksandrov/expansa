<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;
use Generator;

/**
 * Static access to Expansa\Codecs\Csv.
 *
 * @method static array     decode(string $input, string $delimiter = 'auto', string $enclosure = 'auto', string $linebreak = 'auto', string $encoding = 'auto')
 * @method static Generator iterate(string $input, string $delimiter = 'auto', string $enclosure = 'auto', string $linebreak = 'auto', string $encoding = 'auto')
 * @method static string    encode(array $items, string $delimiter = ',', string $enclosure = '"', string $linebreak = "\r\n")
 */
class Csv extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Codecs\Csv';
    }
}
