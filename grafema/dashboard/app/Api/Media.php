<?php

namespace dashboard\app\Api;

use Grafema\Error;
use Grafema\File;
use Grafema\I18n;
use Grafema\Post\Post;
use Grafema\Sanitizer;
use Grafema\Url;

class Media extends \Grafema\Api\Handler
{
	/**
	 * Endpoint name.
	 */
	public string $endpoint = 'media';

	/**
	 * Get media files.
	 *
	 * @since 2025.1
	 */
	public static function get() {
		$media = \Grafema\Media::get(
			[
				'per_page' => 60,
			]
		);

		return [
			'posts' => $media,
		];
	}

	/**
	 * Upload new file to media.
	 *
	 * @since 2025.1
	 */
	public static function upload(): array
	{
		$errors = [];
		$posts  = [];
		$files  = Sanitizer::array( $_FILES ?? [] );
//		exit;
//		print_r( $_FILES );
//		exit;
		foreach ( $files as $file ) {
			$filename = $file['name'] ?? '';
			$postID   = \Grafema\Media::upload( $file );
			if ( $postID instanceof Error ) {
				$errors[ $filename ] = Error::get();
			} else {
				$posts[] = Post::get( 'media', $postID );
			}
		}

		return [
			'notice'   => empty( $errors ) ? I18n::_f( '%d files have been successfully uploaded to the library', count( $posts ) ) : '',
			'uploaded' => count( $posts ) > 0,
			'posts'    => $posts,
			'errors'   => $errors,
		];
	}

	/**
	 * Upload files from external url.
	 *
	 * @since 2025.1
	 */
	public static function grab(): array {
		$errors = [];
		$files  = [];
		$urls   = Url::extract( $_POST['urls'] ?? '' );
		echo '<pre>';
		if ( $urls ) {
			$targetDir = sprintf( '%si/original/', GRFM_UPLOADS );

			foreach ( $urls as $url ) {
				$files[ $url ] = File::grab( $url, $targetDir, function( $file ) {

				} );
			}
		}
		print_r( $files );

		return [
			'notice'   => empty( $errors ) ? I18n::_f( '%d files have been successfully uploaded to the library', count( $files ) ) : '',
			'uploaded' => count( $files ) > 0,
			'files'    => $files,
			'errors'   => $errors,
		];
	}
}
