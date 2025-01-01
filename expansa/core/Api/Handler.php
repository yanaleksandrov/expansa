<?php
namespace Expansa\Api;

use Exception;

abstract class Handler {

	/**
	 * Endpoint
	 *
	 * @var string
	 */
	public string $endpoint = '';

	/**
	 * Root of endpoint
	 *
	 * @var string
	 */
	public string $root = '';

	/**
	 * @var array|string[]
	 */
	public array $request = [];

	/**
	 *
	 *
	 * @var string
	 */
	public string $origin = '*';

	/**
	 * Request URL
	 *
	 * @var string
	 */
	public string $url = '';

	/**
	 * Return format
	 *
	 * @var string
	 */
	protected string $format = 'json';

	/**
	 * Http methods: GET|POST|PUT|DELETE
	 *
	 * @var string
	 */
	protected string $method = '';

	/**
	 * Name of the method to execute
	 *
	 * @var string
	 */
	protected string $action = '';

	/**
	 * @throws Exception
	 */
	public function __construct( string $root ) {
		$this->root    = trim( $root, '/' );
		$this->request = explode( '/', trim( $_SERVER['REQUEST_URI'], '/' ) );
		$this->url     = $_SERVER['REQUEST_URI'] ?? '';
		$this->method  = $_SERVER['REQUEST_METHOD'] ?? '';
		$this->format  = $this->getFormat( pathinfo( $_SERVER['SCRIPT_URI'], PATHINFO_EXTENSION ) ?: 'json' );

		if ( $this->method === 'POST' && isset( $_SERVER['HTTP_X_HTTP_METHOD'] ) ) {
			$httpMethod = $_SERVER['HTTP_X_HTTP_METHOD'];

			$this->method = match ( $httpMethod ) {
				'DELETE' => 'DELETE',
				'PUT'    => 'PUT',
				default  => throw new Exception( 'Unexpected Header' )
			};
		}
	}

	/**
	 * @return bool|string|null
	 * @throws Exception
	 */
	public function run(): bool|string|null {
		$root         = explode( '/', $this->root );
		$diff         = count( $this->request ) - count( $root );
		$request      = array_slice( $this->request, 0, -$diff );
		[ $resource ] = explode( '.', $this->request[2] ?? '' );

		// defining an action to process
		$this->action = $this->map();

		// don't do anything if the request is not an API request, check by the root path in the URL
		if ( ! empty( array_diff( $root, $request ) ) ) {
			return false;
		}

		header( 'Expires: 0' );
		header( 'Pragma: no-cache' );
		header( "Access-Control-Allow-Origin: {$this->origin}" );
		header( "Content-Type: {$this->format}" );

		if ( empty( $resource ) || $resource !== $this->endpoint ) {
			throw new Exception( 'Resource Is Not Found!', 405 );
		}

		if ( method_exists( $this, $this->action ) ) {
			var_dump( $this->action );
			try {
				// calling a method in a child API class
				$response = $this->{$this->action}();

				// TODO: exception in need format
				return match ( $this->format ) {
					'xml'   => ResponseXml::convert( $response ),
					'json'  => ResponseJson::convert( $response ),
					default => throw new Exception( 'Invalid Format', 405 ),
				};
			} catch ( Exception $e ) {
				return new Exception( 'Invalid Method', 405 );
			}
		} else {
			throw new Exception( 'Invalid Method', 405 );
		}
	}

	/**
	 * @return string|null
	 */
	protected function map(): ?string {
		return match ( $this->method ) {
			'GET'    => $this->request ? 'view' : 'index',
			'POST'   => 'create',
			'PUT'    => 'update',
			'DELETE' => 'delete',
			default  => null,
		};
	}

	protected function getFormat( string $extension ) {
		return match ( $extension ) {
			'csv'   => 'text/csv',
			default => sprintf( 'application/%s', $extension ),
		};
	}
}
