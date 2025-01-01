<?php
namespace Grafema;

/**
 * Class Route.
 *
 * @since 2025.1
 */
final class Route extends Routing\Router {

	/**
	 * Store a before middleware route and a handling function to be executed when accessed using one of the specified methods.
	 *
	 * @param string $methods           Allowed methods, | delimited
	 * @param string $pattern           A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function before( string $methods, string $pattern, object|callable $callback ): void {
		self::router()->before( $methods, $pattern, $callback );
	}

	/**
	 * Store a route and a handling function to be executed when accessed using one of the specified methods.
	 *
	 * @param string $methods           Allowed methods, | delimited
	 * @param string $pattern           A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function match( string $methods, string $pattern, callable|object $callback ): void {
		self::router()->match( $methods, $pattern, $callback );
	}

	/**
	 * Shorthand for a route accessed using any method.
	 *
	 * @param string          $pattern  A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function any( string $pattern, callable|object $callback ): void {
		self::router()->match( 'GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $callback );
	}

	/**
	 * Shorthand for a route accessed using GET.
	 *
	 * @param string          $pattern  A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function get( string $pattern, callable|object $callback ): void {
		self::router()->match( 'GET', $pattern, $callback );
	}

	/**
	 * Shorthand for a route accessed using POST.
	 *
	 * @param string          $pattern  A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function post( string $pattern, callable|object $callback ): void {
		self::router()->match( 'POST', $pattern, $callback );
	}

	/**
	 * Shorthand for a route accessed using PATCH.
	 *
	 * @param string          $pattern  A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function patch( string $pattern, callable|object $callback ): void {
		self::router()->match( 'PATCH', $pattern, $callback );
	}

	/**
	 * Shorthand for a route accessed using DELETE.
	 *
	 * @param string          $pattern  A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function delete( string $pattern, callable|object $callback ): void {
		self::router()->match( 'DELETE', $pattern, $callback );
	}

	/**
	 * Shorthand for a route accessed using PUT.
	 *
	 * @param string          $pattern  A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function put( string $pattern, callable|object $callback ): void {
		self::router()->match( 'PUT', $pattern, $callback );
	}

	/**
	 * Shorthand for a route accessed using OPTIONS.
	 *
	 * @param string          $pattern  A route pattern such as /about/system
	 * @param object|callable $callback The handling function to be executed
	 *
	 * @since 2025.1
	 */
	public static function options( string $pattern, callable|object $callback ): void {
		self::router()->match( 'OPTIONS', $pattern, $callback );
	}

	/**
	 * Mounts a collection of callbacks onto a base route.
	 *
	 * @param string   $baseRoute The route sub pattern to mount the callbacks on
	 * @param callable $callback  The callback method
	 *
	 * @since 2025.1
	 */
	public static function middleware( string $baseRoute, callable $callback ): void {
		self::router()->mount( $baseRoute, $callback );
	}

	/**
	 * Execute the router: Loop all defined before middlewares and routes, and execute the handling function if a match was found.
	 *
	 * @param object|callable|null $callback Function to be executed after a matching route was handled (= after router middleware)
	 * @return bool
	 *
	 * @since 2025.1
	 */
	public static function run( object|callable $callback = null ): bool {
		return self::router()->run( $callback );
	}

	/**
	 * Set the 404 handling function.
	 *
	 * @param object|callable|string $matchFn  The function to be executed
	 * @param object|callable|null   $callback The function to be executed
	 *
	 * @since 2025.1
	 */
	public static function set404( object|callable|string $matchFn, object|callable $callback = null ): void {
		self::router()->set404( $matchFn, $callback );
	}

	/**
	 * Triggers 404 response.
	 *
	 * @param mixed|null $match
	 *
	 * @since 2025.1
	 */
	public static function trigger404( mixed $match = null ): void {
		self::router()->trigger404( $match );
	}
}
