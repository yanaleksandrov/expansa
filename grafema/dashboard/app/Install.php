<?php
namespace Dashboard;

use Grafema\Hook;
use Grafema\Route;
use Grafema\Asset;
use Grafema\View;
use Grafema\Url;
use Grafema\Html;
use Grafema\Api;
use Grafema\Patterns\Singleton;

/**
 *
 *
 * @package Grafema
 */
final class Install extends \Grafema\App\App {

	use Singleton;

	/**
	 * Class constructor
	 *
	 * @return void|bool
	 * @since 2025.1
	 */
	public function __construct() {

		/**
		 * Define declare the necessary constants
		 *
		 * @since 2025.1
		 */
		$this->define( 'GRFM_IS_INSTALL', true );

		/**
		 * Add core API endpoints.
		 * Important! If current request is request to API, stop code execution after Api::create().
		 *
		 * @since 2025.1
		 */
		Api::create( '/app/API', sprintf( '%sdashboard/api', GRFM_PATH ) );

		/**
		 * Register new routes
		 *
		 * @since 2025.1
		 */
		$this->route();
	}

	/**
	 * Add router
	 *
	 * @since 2025.1
	 */
	private function route(): void {
		Route::get( '(.*)', function( $slug ) {
			http_response_code( 200 );

			/**
			 * Redirect to installer wizard if Grafema is not installed.
			 *
			 * @since 2025.1
			 */
			if ( $slug !== 'install' ) {
				View::redirect( Url::site( 'install' ) );
				exit;
			}

			/**
			 * Run the installer wizard.
			 *
			 * @since 2025.1
			 */
			$styles = [ 'grafema', 'controls', 'utility', 'phosphor' ];
			foreach ( $styles as $style ) {
				Asset::enqueue( $style, Url::site( 'dashboard/assets/css/' . $style . '.css' ) );
			}

			$scripts = [ 'grafema', 'ajax', 'alpine' ];
			foreach ( $scripts as $script ) {
				$data = [];
				if ( $script === 'grafema' ) {
					$data['data'] = [
						'apiurl' => Url::site( '/api/' ),
					];
				}
				Asset::enqueue( $script, Url::site( 'dashboard/assets/js/' . $script . '.js' ), $data );
			}

			/**
			 * Include assets before calling hooks, but after they are registered.
			 *
			 * @since 2025.1
			 */
			Hook::add( 'grafema_dashboard_header', fn () => Asset::render( '*.css' ) );
			Hook::add( 'grafema_dashboard_footer', fn () => Asset::render( '*.js' ) );

			/**
			 * The administrative panel also has a single entry point.
			 *
			 * @since 2025.1
			 */
			View::print( GRFM_PATH . 'dashboard/install' );
			//echo ( new Html() )->beautify( View::get( GRFM_PATH . 'dashboard/install' ) );

			if ( extension_loaded( 'xhprof' ) ) {
				$xhprof_data = xhprof_disable();
				$xhprof_root = 'C:\OpenServer\domains\grfm.loc\xhprof';
				$xhprof_lib  = $xhprof_root . '\xhprof_lib\utils\xhprof_lib.php';
				$xhprof_runs = $xhprof_root . '\xhprof_lib\utils\xhprof_runs.php';

				// Подключаем необходимые файлы
				include_once $xhprof_lib;
				include_once $xhprof_runs;

				// Сохраняем данные профилирования
				$xhprof_runs = new \XHProfRuns_Default();
				$run_id      = $xhprof_runs->save_run( $xhprof_data, 'my_app' );
				$report_url  = "http://grfm.loc/xhprof/xhprof_html/index.php?run={$run_id}&source=my_app";

				// Выводим отчёт на текущей странице
				echo '<code>';
				echo "<a href='{$report_url}' target='_blank'>Ссылка на ваш отчет</a>";
				echo '</code>';
			}
		} );

		Route::run( fn() => die() );
	}
}
