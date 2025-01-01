<?php

namespace Expansa\Cache;

trait Traits {

	/**
	 * DB table name.
	 *
	 * @var string
	 * @since 2025.1
	 */
	protected static string $table = 'cache';

	/**
	 * Holds the cached data.
	 *
	 * @var array
	 *
	 * @since 2025.1
	 */
	protected static array $cache = [];

	/**
	 * Holds the locked keys.
	 *
	 * @var array
	 *
	 * @since 2025.1
	 */
	protected static array $locks = [];
}
