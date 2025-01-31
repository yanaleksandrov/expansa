<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Error;
use Expansa\Patterns\Facade;

/**
 * @method static string|Error encode( mixed $value, bool $ascii = false, bool $pretty = false, bool $forceObjects = false )
 * @method static mixed        decode( string $json, bool $forceArrays = false )
 * @method static bool         isValid( mixed $data )
 */
class Json extends Facade
{
	protected static function getStaticClassAccessor(): string
	{
		return '\Expansa\Codecs\Json';
	}
}