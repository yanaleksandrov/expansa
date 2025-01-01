<?php

declare(strict_types=1);

namespace Grafema;

use Grafema\Facades\Facade;

/**
 * @method static string     encode(array $array): string
 * @method static null|array decode(string $string): ?array
 */
class Codec extends Facade
{
	protected static function getStaticClassAccessor(): string
	{
		return 'Grafema\Algorithms\Huff\Encoder';
	}
}