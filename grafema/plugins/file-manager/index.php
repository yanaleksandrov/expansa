<?php
use Grafema\Asset;
use Grafema\Hook;
use Grafema\I18n;
use Grafema\Is;
use Grafema\Tree;

/**
 * Boilerplate plugin.
 *
 * @since 2025.1
 */
return new class extends Grafema\Plugin {

	public function __construct() {
		$this
			->setName( 'File Manager' )
			->setVersion( '2025.2' )
			->setAuthor( 'Grafema Team' )
			->setDescription( I18n::_t( 'Tool for ability to edit, delete, upload, download, copy and paste files and folders.' ) );
	}

	public static function launch(): void
	{
		if ( ! Is::dashboard() ) {
			return;
		}

		// TODO: переделать подключение файлов плагинов
		Hook::add( 'grafema_view_part', function( $filepath ) {
			if ( $filepath === GRFM_DASHBOARD . 'views/file-manager.php' ) {
				$filepath = __DIR__ . '/views/file-manager.php';
			}
			return $filepath;
		} );

		Asset::enqueue( 'file-manager', '/plugins/file-manager/assets/css/main.css' );

		Tree::attach( 'core-panel-menu', fn ( Tree $tree ) => $tree->addItems(
			[
				[
					'id'           => 'file-manager',
					'url'          => 'file-manager',
					'title'        => I18n::_t( 'File Manager' ),
					'capabilities' => ['manage_options'],
					'icon'         => 'ph ph-folder-open',
					'position'     => 800,
				],
			]
		) );
	}

	public static function activate()
	{
		// do something when plugin is activated
	}

	public static function deactivate()
	{
		// do something when plugin is deactivated
	}

	public static function install()
	{
		// do something when plugin is installed
	}

	public static function uninstall()
	{
		// do something when plugin is uninstalled
	}
};
