<?php
/**
 * Requests for PHP, an HTTP library.
 *
 * @copyright 2012-2023 Requests Contributors
 * @license   https://github.com/WordPress/Requests/blob/stable/LICENSE ISC
 * @link      https://github.com/WordPress/Requests
 */

namespace Expansa\Http\Contracts;

use Expansa\Http\Hooks;

/**
 * Authentication provider interface
 *
 * Implement this interface to act as an authentication provider.
 *
 * Parameters should be passed via the constructor where possible, as this
 * makes it much easier for users to use your provider.
 *
 * @see \Expansa\Http\Hooks
 *
 * @package Requests\Authentication
 */
interface Auth {
	/**
	 * Register hooks as needed
	 *
	 * This method is called in {@see \Expansa\Http\Requests::request()} when the user
	 * has set an instance as the 'auth' option. Use this callback to register all the
	 * hooks you'll need.
	 *
	 * @see \Expansa\Http\Hooks::register()
	 * @param \Expansa\Http\Hooks $hooks Hook system
	 */
	public function register(Hooks $hooks);
}
