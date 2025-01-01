<?php
/**
 * This file is part of Grafema CMS.
 *
 * @link     https://www.grafema.io
 * @contact  team@core.io
 * @license  https://github.com/grafema-team/grafema/LICENSE.md
 */

namespace Grafema\Csrf\Providers;

use Grafema\Csrf\Interfaces\Provider;

class NativeSessionProvider implements Provider
{
	/**
	 * Get a session value.
	 */
	public function get( string $key ): mixed
	{
		return $_SESSION[$key] ?? null;
	}

	/**
	 * Set a session value.
	 */
	public function set( string $key, mixed $value ): void
	{
		$_SESSION[$key] = $value;
	}
}
