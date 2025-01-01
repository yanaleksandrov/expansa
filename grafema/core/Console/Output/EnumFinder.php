<?php

declare(strict_types=1);

namespace Grafema\Console\Output;

/**
 * Class Command.
 *
 * @package cli
 */
trait EnumFinder
{
	/**
	 * Find the color by name, case-insensitive.
	 *
	 * @param mixed $cases
	 * @param string $value
	 * @return mixed
	 */
	public static function fromName(mixed $cases, string $value): mixed
	{
		$value = strtoupper($value);
		foreach ($cases as $case) {
			if ($case->name === $value) {
				return $case;
			}
		}
		return null;
	}
}
