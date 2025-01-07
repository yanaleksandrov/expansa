<?php
namespace dashboard\app\Api;

use app\Comments;
use app\Option;
use app\Slug;
use app\Taxonomy;
use app\Term;
use app\User;
use Expansa\App\App;
use Expansa\Db;
use Expansa\Error;
use Expansa\File;
use Expansa\Options;
use Expansa\Safe;
use Expansa\Users\Users;

class System extends \Expansa\Api\Handler
{

	/**
	 * Endpoint name.
	 */
	public string $endpoint = 'system';

	/**
	 * Check the compliance of the server with the minimum requirements.
	 *
	 * @since 2025.1
	 */
	public static function test(): array
	{
		$checks = [
			'connection' => false,
			'pdo'        => false,
			'curl'       => false,
			'mbstring'   => false,
			'gd'         => false,
			'memory'     => 128,
			'php'        => '8.1',
			'mysql'      => '5.6',
		];

		$database = Safe::data(
			$_POST,
			[
				'database' => 'trim',
				'username' => 'trim',
				'password' => 'trim',
				'host'     => 'trim',
				'prefix'   => 'trim',
			]
		)->apply();

		Db::init( $database );

		$data = array_map(fn ($check, $value) => [
			$check => match ($check) {
				'php',
				'mysql',
				'memory',
				'connection' => App::check( $check, $value ),
				default      => App::has( $check ),
			}
		], array_keys($checks), $checks);

		return array_merge(...$data);
	}

	/**
	 * Run Expansa installation.
	 *
	 * @since 2025.1
	 */
	public static function install(): array
	{
		$protocol = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' );
		$siteurl  = $protocol . $_SERVER['SERVER_NAME'];

		// TODO: check sanitize rules & add validator
		[ $site, $userdata, $database ] = Safe::data(
			$_POST,
			[
				'site.name'     => 'trim',
				'site.tagline'  => 'trim',
				'site.url'      => "trim:{$siteurl}|url",
				'user.login'    => 'trim',
				'user.email'    => 'email',
				'user.password' => 'trim',
				'db.database'   => 'trim',
				'db.username'   => 'trim',
				'db.password'   => 'trim',
				'db.host'       => 'trim',
				'db.prefix'     => 'snakecase',
			]
		)->values();

		/**
		 * The check for connection to the database should have already been passed by this point.
		 * Therefore, just fill in the file env.php data and immediately connect it.
		 *
		 * @since 2025.1
		 */
		$config = EX_PATH . 'env.php';
		if ( ! file_exists( $config ) ) {
			(new File( EX_PATH . 'env-sample.php' ))->copy( 'env' )->rewrite(
				array_combine(
					[
						'db.name',
						'db.username',
						'db.password',
						'db.host',
						'db.prefix'
					],
					$database
				)
			);
		}

		require_once $config;

		Db::init();

		/**
		 * Creating the necessary tables in the database
		 *
		 * @since 2025.1
		 */
		Term\Schema::migrate();
		User\Schema::migrate();
		Slug::migrate();
		Option::migrate();
		Comments::migrate();
		Taxonomy::migrate();

		Db::updateSchema();

		/**
		 * Fill same options
		 *
		 * @since 2025.1
		 */
		Option::update( 'site', $site );

		$user = User::add( $userdata );
		if ( $user instanceof User ) {
			User::login( $userdata );

			return [
				'installed' => $user instanceof User,
			];
		}

		return Error::get();
	}
}
