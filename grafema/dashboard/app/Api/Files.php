<?php
/**
 * This file is part of Grafema CMS.
 *
 * @link     https://www.grafema.io
 * @contact  team@core.io
 * @license  https://github.com/grafema-team/grafema/LICENSE.md
 */

namespace dashboard\app\Api;

use Dashboard\Form;
use Grafema\File;
use Grafema\Sanitizer;
use Grafema\Support\Csv;
use Grafema\View;

class Files extends \Grafema\Api\Handler
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
				$uploadedFile = ( new File() )->upload( $file )->relocate( GRFM_UPLOADS . 'i/' );

				if ( ! $uploadedFile instanceof File ) {
					continue;
				}

				$filepath = Sanitizer::path( $uploadedFile->path ?? '' );
				$rows     = Csv::import( $filepath );

				View::get(
					GRFM_DASHBOARD . 'forms/posts-import-fields',
					[
						'samples'  => $rows[0] ?? [],
						'filepath' => $filepath,
					]
				);

				return [
					'fields' => Form::get( GRFM_DASHBOARD . 'forms/grafema-post-import-fields.php', true ),
				];
			}
		}
		return [];
	}
}
