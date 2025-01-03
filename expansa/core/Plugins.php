<?php

declare(strict_types=1);

namespace Expansa;

final class Plugins extends Extensions\Provider {

	/**
	 *
	 *
	 * @param callable $callback Callback function used for get plugins paths.
	 * @return void
	 * @since 2025.1
	 */
	public static function register( callable $callback ): void {
		self::enqueue( $callback, 'plugins' );

		foreach ( self::$plugins as $plugin ) {
			$plugin instanceof Extensions\Skeleton && $plugin::launch();
		}
	}

	/**
	 * Activate all registered extensions.
	 *
	 * Calls the `activate()` method on each registered plugin, allowing them to perform necessary initialization tasks.
	 *
	 * @since 2025.1
	 */
	public static function activate(): void {
		foreach ( self::$plugins as $plugin ) {
			$plugin instanceof Extensions\Skeleton && $plugin::activate();
		}
	}

	/**
	 * Deactivate all registered extensions.
	 *
	 * Calls the `deactivate()` method on each registered plugin, allowing them to clean up resources or undo changes made during activation.
	 *
	 * @since 2025.1
	 */
	public static function deactivate(): void {
		foreach ( self::$plugins as $plugin ) {
			$plugin instanceof Extensions\Skeleton && $plugin::deactivate();
		}
	}

	/**
	 * Install all registered extensions.
	 *
	 * Calls the `install()` method on each registered plugin, allowing them to perform installation tasks.
	 *
	 * @since 2025.1
	 */
	public static function install(): void {
		foreach ( self::$plugins as $plugin ) {
			$plugin instanceof Extensions\Skeleton && $plugin::install();
		}
	}

	/**
	 * Uninstall all registered extensions.
	 *
	 * Calls the `uninstall()` method on each registered plugin, allowing them to clean up resources or remove associated assets.
	 *
	 * @since 2025.1
	 */
	public static function uninstall(): void {
		foreach ( self::$plugins as $plugin ) {
			$plugin instanceof Extensions\Skeleton && $plugin::uninstall();
		}
	}

	/**
	 * Checks the plugins directory and retrieve all plugin files with plugin data.
	 *
	 * Expansa only supports plugin files in the base plugins directory
	 * (plugins) and in one directory above the plugins directory
	 * (plugins/my-plugin). The file it looks for has the plugin data
	 * and must be found in those two locations. It is recommended to keep your
	 * plugin files in their own directories.
	 *
	 * @since 2025.1
	 *
	 * @return array[] Array of arrays of plugin data, keyed by plugin file name.
	 */
	public static function get(): array {
		return self::$plugins;
	}
}
