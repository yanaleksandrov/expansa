<?php
/**
 * This file is part of Expansa CMS.
 *
 * @link     https://www.expansa.io
 * @contact  team@core.io
 * @license  https://github.com/expansa-team/expansa/LICENSE.md
 */

namespace Expansa\Csrf\Providers;

use Expansa\Csrf\Interfaces\Provider;

class NativeHttpOnlyCookieProvider implements Provider
{
	const HOUR_IN_SECONDS = 3600;

	/**
	 * Get a cookie value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get( string $key ): mixed
	{
		return $_COOKIE[$key] ?? null;
	}

	/**
	 * Set a cookie value.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( string $key, mixed $value ): void
	{
		setcookie( $key, $value, time() + self::HOUR_IN_SECONDS, '/', '', false, true );
	}
}
