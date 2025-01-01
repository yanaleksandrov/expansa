<?php
namespace Expansa;

final class Themes extends Extensions\Provider {

	/**
	 * Extract all implements on Extensions\Skeleton & add a plugin to the list of registered extensions.
	 *
	 * @param callable $callback Callback function used for get plugins paths.
	 * @return void
	 * @since 2025.1
	 */
	public static function register( callable $callback ): void {
		self::enqueue( $callback, 'themes' );

		foreach ( self::$themes as $theme ) {
			$theme instanceof Extensions\Skeleton && $theme::launch();
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
		foreach ( self::$themes as $theme ) {
			$theme instanceof Extensions\Skeleton && $theme::activate();
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
		foreach ( self::$themes as $theme ) {
			$theme instanceof Extensions\Skeleton && $theme::deactivate();
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
		foreach ( self::$themes as $theme ) {
			$theme instanceof Extensions\Skeleton && $theme::install();
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
		foreach ( self::$themes as $theme ) {
			$theme instanceof Extensions\Skeleton && $theme::uninstall();
		}
	}

	/**
	 *
	 *
	 * @since 2025.1
	 *
	 * @return array[]
	 */
	public static function get(): array {
		return self::$themes;
	}
}
