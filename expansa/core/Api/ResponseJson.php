<?php
/**
 * This file is part of Expansa CMS.
 *
 * @link     https://www.expansa.io
 * @contact  team@core.io
 * @license  https://github.com/expansa-team/expansa/LICENSE.md
 */

namespace Expansa\Api;

use Exception;

/**
 * @since 2025.1
 */
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
