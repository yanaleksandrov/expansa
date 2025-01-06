<?php
/**
 * This file is part of Expansa CMS.
 *
 * @link     https://www.expansa.io
 * @contact  team@core.io
 * @license  https://github.com/expansa-team/expansa/LICENSE.md
 */

namespace dashboard\app\Api;

use Dashboard\Form;
use Expansa\File;
use Expansa\Safe;
use Expansa\Codec\Csv;
use Expansa\View;

class Files extends \Expansa\Api\Handler
{
	/**
	 * Endpoint name.
	 */
	public string $endpoint = 'files';

	/**
	 * Upload files from external url.
	 *
	 * @since 2025.1
	 */
	public static function upload(): File|array
	{
		$files = $_FILES ?? [];
		if ( $files ) {
			foreach ( $files as $file ) {
				$uploadedFile = ( new File() )->upload( $file )->relocate( EX_UPLOADS . 'i/' );

				if ( ! $uploadedFile instanceof File ) {
					continue;
				}

				$filepath = Safe::path( $uploadedFile->path ?? '' );
				$rows     = Csv::import( $filepath );

				View::get(
					EX_DASHBOARD . 'forms/posts-import-fields',
					[
						'samples'  => $rows[0] ?? [],
						'filepath' => $filepath,
					]
				);

				return [
					'fields' => Form::get( EX_DASHBOARD . 'forms/expansa-post-import-fields.php', true ),
				];
			}
		}
		return [];
	}
}
