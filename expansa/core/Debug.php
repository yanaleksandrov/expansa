<?php

declare(strict_types=1);

namespace Expansa;

/**
 * A utility class for debugging PHP applications. Provides methods to manage error reporting,
 * measure execution time, memory usage, and generate detailed error outputs. This class is
 * designed to be used in a development environment to facilitate troubleshooting and performance
 * analysis.
 *
 * @package Expansa
 * @final
 */
final class Debug {

	public static function configure() {

	}

	public static function launch(): void
	{
		if ( defined( 'EX_DEBUG' ) && EX_DEBUG ) {
			ini_set( 'error_reporting', E_ALL );
			ini_set( 'display_errors', 1 );
			ini_set( 'display_startup_errors', 1 );
			error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
		}
	}

	/**
	 * Считает время выполнения PHP кода (в секундах).
	 *
	 * @param string $phase run             - включает подсчет: первый раз или после паузы (по умолчанию).
	 *                      start           - очищает все кэши и включает подсчет - clear & run.
	 *                      get             - получает разницу, между предыдущим вызовом функции.
	 *                      getall|end|stop - получает разницу, между первым вызовом функции (run).
	 *                      pause           - временная остановка подсчета. exec_time() для продолжения.
	 *                      clear           - полностью очищает результат. exec_time() для начала нового подсчета.
	 * @param int $round    до скольки знаков после запятой округлять результат
	 * @param string $txt
	 * @return float|int|string Example 0.03654
	 *
	 * @ver: 3.4.3
	 */
	public static function timer( string $phase = 'run', int $round = 3, string $txt = 's' ): float|int|string
	{
		static $prev_time, $collect;

		if ( $phase === 'start' ) {
			$collect = $prev_time = 0;
		} elseif ( $phase === 'clear' ) {
			return $collect = $prev_time = 0;
		}

		[$usec, $sec] = explode( ' ', microtime() );
		$mctime       = bcadd( $usec, $sec, 8 );

		if ( $prev_time ) {
			$exectime = $mctime - $prev_time; // bcsub( $mctime, $prev_time, 8 );
			$collect += $exectime;            // bcadd( $collect, $exectime, 8 );
		}
		$prev_time = $mctime;

		return match ( $phase ) {
			'getall',
			'end',
			'stop'    => round( $collect ?? 0, $round ) . $txt,
			'get'     => round( $exectime, $round ) . $txt,
			default   => 0,
		};
	}
	
	/**
	 * Возвращает количество памяти в байтах, которое было выделено PHP-скрипту.
	 *
	 * @param bool $real_usage
	 * @return string
	 */
	public static function memory( bool $real_usage = false ): string
	{
		return self::convert( memory_get_usage( $real_usage ) );
	}

	/**
	 * Возвращает пиковое значение объема памяти, выделенное PHP.
	 *
	 * @param bool $real_usage
	 * @return string
	 */
	public static function memory_peak( bool $real_usage = false ): string
	{
		return self::convert( memory_get_peak_usage( $real_usage ) );
	}

	/**
	 * Возвращает процент используемой памяти от общего количества выделенной.
	 *
	 * @param bool $relative
	 * @return false|string
	 */
	public static function memory_limit( bool $relative = true ): bool|string
	{
		if ( $relative ) {
			return round( memory_get_usage() / ( (int) ini_get( 'memory_limit' ) * 1024 * 1024 ) * 100, 2 ) . '%';
		}

		return ini_get( 'memory_limit' );
	}

	/**
	 * Converts bytes.
	 *
	 * @param int $size
	 * @return string
	 */
	public static function convert( int $size ): string
	{
		$i = floor( log( $size, 1024 ) );

		return round( $size / pow( 1024, $i ), 1 ) . ['b', 'kb', 'mb', 'gb'][$i];
	}

	/**
	 * TODO: output some debug code only for specified user.
	 *
	 * @param int $user_id User ID
	 * @param ?callable $function
	 */
	public static function only( int $user_id, ?callable $function ): void
	{
		echo '<pre>';
		$function();
		echo '</pre>';
	}

	/**
	 * Generates a backtrace.
	 *
	 * @since 2025.1
	 */
	private static function backtrace(): array
	{
		return debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
	}

	/**
	 * Parse errors to array.
	 *
	 * @since 2025.1
	 */
	public static function parse( mixed $error ): array {
		$title = I18n::_t( 'Fatal Error' );

		$description = I18n::_f( 'Find on line :lineNumber in file :filepath', $error->getLine(), $error->getFile() );
		$description = preg_replace( '/[a-z0-9_\-]*\.php/i','$1<u>$0</u>', $description );
		$description = preg_replace( '/[0-9]/i','$1<em>$0</em>', $description );
		$description = preg_replace( '/[\(\)#\[\]\':]/i','$1<ss>$0</ss>', $description );

		$traces     = [];
		$tracesList = $error->getTrace();
		if ( $tracesList ) {
			foreach ( $tracesList as $trace ) {
				if ( empty( $trace['file'] ) ) {
					continue;
				}

				$traces[] = (object) [
					'file' => $trace['file'] ?? '',
					'line' => $trace['line'] ?? '',
				];
			}
		}

		$context = $error->getMessage();
		$details = match( true ) {
			$error instanceof \TypeError => self::parseTypeError( $error ),
			default => [],
		};

		$code = self::parseErrorCode( $error );

		return compact( 'title', 'description', 'context', 'details', 'traces', 'code' );
	}

	private static function parseTypeError( \TypeError $error ): array {
		$data = [];

		$errorTrace     = current( $error->getTrace() );
		$errorTraceArgs = $errorTrace['args'] ?? [];
		if ( $errorTraceArgs ) {
			foreach ( $errorTraceArgs as $key => $value ) {
				$data[] = (object) [
					'key'   => $key,
					'type'  => gettype( $value ),
					'value' => $value,
				];
			}
		}
		return $data;
	}

	private static function parseErrorCode( mixed $error ): string {
		$trace = $error->getTrace();

		$code = '';
		if ( empty( $trace[0] ) ) {
			return $code;
		}

		try {
			$file = $error->getFile();
			$line = $error->getLine();

			// get lines of code around the error so that the context is visible
			$lines = file( $file );
			$code  = implode('', array_slice( $lines, max( 0, $line - 10 ), 30 ) );
		} catch ( \ReflectionException $e ) {}

		return trim( $code );
	}
}
