<?php

declare(strict_types=1);

namespace Expansa\Csrf;

use Expansa\Csrf\Exception\InvalidCsrfTokenException;
use Expansa\Csrf\Interfaces\Provider;
use Exception;

/**
 * Class CSRF is standalone library to provides Cross-Site Request Forgery (CSRF) protection by generating and validating tokens.
 *
 * $provider = new Csrf\Providers\NativeSessionProvider();
 * $csrf     = new Csrf\Csrf( $provider );
 *
 * $token = $csrf->generate( 'my_token' );
 *
 * try {
 * 	$csrf->check( 'my_token', $token );
 * } catch ( Csrf\Exception\InvalidCsrfTokenException $e ) {
 * 	return $e->getMessage();
 * }
 *
 * @see   https://github.com/gilbitron/EasyCSRF
 */
class Csrf
{
	protected Provider $session;

	/**
	 * Session prefix.
	 */
	protected string $session_prefix = 'expansa_';

	/**
	 * Csrf constructor.
	 *
	 * @param Provider $provider
	 */
	public function __construct( Provider $provider )
	{
		$this->session = $provider;
	}

	/**
	 * Generate a CSRF token.
	 *
	 * @param string $key Key for this token
	 * @return string|null
	 */
	public function generate( string $key ): ?string
	{
		$key = $this->sanitizeKey( $key );

		try {
			$token = $this->createToken();
		} catch ( Exception $e ) {
			return null;
		}

		$this->session->set( $this->session_prefix . $key, $token );

		return $token;
	}

	/**
	 * Check the CSRF token is valid.
	 *
	 * @param string   $key      Key for this token
	 * @param string   $token    The token string (usually found in $_POST)
	 * @param int|null $timespan Makes the token expire after $timespan seconds (null = never)
	 * @param bool     $multiple Makes the token reusable and not one-time (Useful for ajax-heavy requests)
	 *
	 * @throws InvalidCsrfTokenException
	 */
	public function check( string $key, string $token, int $timespan = null, bool $multiple = false ): void
	{
		$key = $this->sanitizeKey( $key );

		if ( ! $token ) {
			throw new InvalidCsrfTokenException( 'Invalid CSRF token' );
		}

		$sessionToken = $this->session->get( $this->session_prefix . $key );
		if ( ! $sessionToken ) {
			throw new InvalidCsrfTokenException( 'Invalid CSRF session token' );
		}

		if ( ! $multiple ) {
			$this->session->set( $this->session_prefix . $key, $token );
		}

		if ( $this->referralHash() !== substr( base64_decode( $sessionToken ), 10, 40 ) ) {
			throw new InvalidCsrfTokenException( 'Invalid CSRF token' );
		}

		if ( $token !== $sessionToken ) {
			throw new InvalidCsrfTokenException( 'Invalid CSRF token' );
		}

		// Check for token expiration
		if ( is_int( $timespan ) && ( intval( substr( base64_decode( $sessionToken ), 0, 10 ) ) + $timespan ) < time() ) {
			throw new InvalidCsrfTokenException( 'CSRF token has expired' );
		}
	}

	/**
	 * Sanitizer the session key.
	 */
	protected function sanitizeKey( string $key ): string
	{
		return preg_replace( '/[^a-zA-Z0-9]+/', '', $key );
	}

	/**
	 * Create a new token. time() is used for token expiration.
	 *
	 * @throws Exception
	 */
	protected function createToken(): string
	{
		return base64_encode( time() . $this->referralHash() . $this->randomString() );
	}

	/**
	 * Return a unique referral hash.
	 */
	protected function referralHash(): string
	{
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sha1( $_SERVER['REMOTE_ADDR'] );
		}

		return sha1( $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] );
	}

	/**
	 * Generate a random string.
	 *
	 * @throws Exception
	 */
	protected function randomString(): string
	{
		return sha1( random_bytes( 32 ) );
	}
}
