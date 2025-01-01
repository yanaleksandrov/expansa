<?php

declare(strict_types=1);

namespace Grafema;

use Grafema\Facades\Facade;

/**
 * @method static array       decode( $filepathOrData, string $delimiter = 'auto', string $enclosure = 'auto', string $linebreak = 'auto' )
 * @method static bool|string encode( $items, string $delimiter = ',', string $enclosure = '"', string $linebreak = "\r\n" )
 */
class Csv extends Facade
{
	protected static function getStaticClassAccessor(): string
	{
		return 'Grafema\Support\Csv';
	}
}