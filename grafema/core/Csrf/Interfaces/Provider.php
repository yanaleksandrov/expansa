<?php
/**
 * This file is part of Grafema CMS.
 *
 * @link     https://www.grafema.io
 * @contact  team@core.io
 * @license  https://github.com/grafema-team/grafema/LICENSE.md
 */

namespace Grafema\Csrf\Interfaces;

interface Provider
{
	/**
	 * Get a session value.
	 */
	public function get( string $key ): mixed;

	/**
	 * Set a session value.
	 */
	public function set( string $key, mixed $value ): void;
}
