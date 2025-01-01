<?php
namespace dashboard\app\Api;

use Grafema;
use Grafema\I18n;
use Grafema\Mail;
use Grafema\Sanitizer;
use Grafema\Url;
use Grafema\View;

class User implements Grafema\Api\Crud {

	/**
	 * Endpoint name.
	 */
	public string $endpoint = 'user';

	/**
	 * Create item.
	 *
	 * @url    POST api/user
	 */
	public function create(): array
	{
		return [
			'method' => 'POST create user',
		];
	}

	/**
	 * Get all items.
	 *
	 * @url    GET api/user
	 */
	public function index(): array
	{
		return [
			'method' => 'GET user list',
		];
	}

	/**
	 * Update item by ID.
	 *
	 * @url    PUT api/user/{id}
	 */
	public function update(): array
	{
		$currentUser = Grafema\User::current();
		$userdata    = $_REQUEST + [ 'id' => $currentUser->id ];

		Grafema\User::update( $userdata, function ( Grafema\Field $field ) {
			$fields = ( new Sanitizer(
				$_REQUEST,
				[
					'bio'     => 'trim',
					'toolbar' => 'bool',
					'format'  => 'text',
					'locale'  => 'locale',
				]
			) )->apply();

			foreach ( $fields as $key => $value ) {
				$field->mutate( $key, $value );
			}
		} );

		return [
			[
				'target'   => 'body',
				'method'   => 'notify',
				'fragment' => I18n::_t( 'User is updated' ),
			]
		];
	}

	/**
	 * Remove item by ID.
	 *
	 * @url    DELETE api/user/$id
	 */
	public function delete(): array
	{
		return [
			'method' => 'DELETE remove user by ID',
		];
	}

	/**
	 * Check the compliance of the server with the minimum requirements.
	 *
	 * @url    GET api/user/sign-in
	 */
	public static function signIn(): array
	{
		$user = Grafema\User::login( $_POST );
		if ( $user instanceof Grafema\Error ) {
			return [
				[
					'target'   => 'body',
					'method'   => 'notify',
					'fragment' => $user->getError( 'user-login' ),
				],
			];
		}

		return [
			[
				'target'   => 'body',
				'method'   => 'redirect',
				'fragment' => Url::site( 'dashboard' ),
			],
		];
	}

	/**
	 * Sign up user.
	 *
	 * @since 2025.1
	 */
	public static function signUp(): array
	{
		$user = Grafema\User::add( $_REQUEST ?? [] );
		if ( $user instanceof Grafema\User ) {
			return [
				'signed-up' => true,
				[
					'target'   => 'body',
					'method'   => 'redirect',
					'fragment' => Url::sign_in(),
				],
			];
		}
		return $user;
	}

	/**
	 * Reset user password.
	 *
	 * @since 2025.1
	 */
	public static function resetPassword(): array
	{
		$email = Sanitizer::email( $_REQUEST['email'] ?? '' );
		$user  = Grafema\User::get( $email, 'email' );
		if ( $user instanceof Grafema\User ) {
			$mail_is_sent = Mail::send(
				$email,
				I18n::_t( 'Instructions for reset password' ),
				View::get(
					GRFM_DASHBOARD . 'views/mails/wrapper',
					[
						'body_template' => GRFM_DASHBOARD . 'views/mails/reset-password',
					]
				)
			);

			return [
				'mail-is-sent' => $mail_is_sent,
			];
		}
		return $user;
	}

	/**
	 * Reset user password.
	 *
	 * @since 2025.1
	 */
	public static function passwordUpdate(): array {

	}
}
