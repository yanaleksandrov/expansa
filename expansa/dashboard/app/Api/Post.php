<?php
namespace dashboard\app\Api;

use Expansa;
use Expansa\Error;
use Expansa\I18n;
use Expansa\Sanitizer;

class Post implements Expansa\Api\Contracts\Crud {

	/**
	 * Endpoint name.
	 */
	public string $endpoint = 'post';

	/**
	 * Get all items.
	 *
	 * @url    GET api/posts
	 */
	public function index(): array
	{
		return [
			'method' => 'PUT update user by ID',
		];
	}

	/**
	 * Create item.
	 *
	 * @url    POST api/posts
	 */
	public function create(): array
	{
		$fields = ( new Sanitizer(
			$_POST ?? [],
			[
				'limits'     => 'absint',
				'period'     => 'text',
				'start-date' => 'datetime',
				'end-date'   => 'datetime',
				'sites'      => 'trim|sitesList',
			]
		) )->extend( 'sitesList', function( $value ) {
			$sitesList = array_map( 'trim', explode( ',', $value ) );

			return array_filter( $sitesList, fn( $url ) => filter_var( $url, FILTER_VALIDATE_URL ) );
		} )->apply();

		$title  = Sanitizer::text( $_POST['app-name'] ?? '' );
		$status = Sanitizer::text( $_POST['status'] ?? '' );
		$type   = Sanitizer::text( $_POST['post-type'] ?? '' );
		if ( ! $type ) {
			return Error::add( 'post-type-create', I18n::_t( 'Post type is missing' ) );
		}

		// TODO:: title make unique by user for "api-keys" post type
//		$user = User::current();
//		if ( $user instanceof User ) {
//			$suffix = 1;
//			while ( Expansa\Post::getByTitle( $type, $title ) instanceof Expansa\Post ) {
//				$title = sprintf( '%s %s', $title, $suffix++ );
//			}
//		}

		$post = Expansa\Post::add( $type, compact( 'title', 'status', 'fields' ) );
		echo '<pre>';
		print_r( $fields );
		print_r( $post );
		echo '</pre>';

		return [
			'method' => 'POST create user',
		];
	}

	/**
	 * Update item by ID.
	 *
	 * @url    PUT api/posts/$id
	 */
	public function update(): array
	{
		return [
			'method' => 'PUT update user by ID',
		];
	}

	/**
	 * Remove item by ID.
	 *
	 * @url    DELETE api/posts/$id
	 */
	public function delete(): array
	{
		return [
			'method' => 'DELETE remove user by ID',
		];
	}
}
