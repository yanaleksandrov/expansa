<?php
/**
 * This file is part of Expansa CMS.
 *
 * @link     https://www.expansa.io
 * @contact  team@core.io
 * @license  https://github.com/expansa-team/expansa/LICENSE.md
 */

namespace Expansa\Csrf\Interfaces;

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
