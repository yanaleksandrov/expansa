<?php

declare(strict_types=1);

namespace Expansa\Api;

use Exception;

class ResponseJson
{
	/**
	 * Convert array to JSON.
	 *
	 * @param array $response
	 * @return string
	 * @throws Exception
	 */
	public static function convert( array $response ): string
	{
		$json = json_encode( $response, JSON_UNESCAPED_UNICODE );
		if ( $json === false ) {
			throw new Exception( 'Error encoding array to JSON: ' . json_last_error_msg() );
		}
		return $json;
	}
}
