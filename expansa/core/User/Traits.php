<?php
namespace Expansa\User;

/**
 * @since 2025.1
 */
trait Traits {

	/**
	 * Session key
	 *
	 * @since 2025.1
	 * @var   string
	 */
	private static string $session_id = EX_DB_PREFIX . 'user_logged';

	/**
	 * Current user data.
	 *
	 * @since 2025.1
	 */
	private static $current;
}
