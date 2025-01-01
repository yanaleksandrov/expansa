<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;

/**
 * @method static string     encode(array $array): string
 * @method static null|array decode(string $string): ?array
 */
class Codec extends Facade
{
	protected static function getStaticClassAccessor(): string
	{
		return 'Expansa\Algorithms\Huff\Encoder';
	}
}