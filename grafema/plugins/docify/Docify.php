<?php
use Grafema\I18n;
use Grafema\Post;
use Grafema\Tree;

/**
 * Docify plugin.
 *
 * @since 2025.1
 */
return new class extends Grafema\Plugin {

	public function __construct() {
		$this
			->setName( 'Docify' )
			->setVersion( '2024.9' )
			->setAuthor( 'Grafema Team' )
			->setDescription( I18n::_t( 'Simple way to create docs for your plugins' ) );
	}

	public static function launch()
	{
		spl_autoload_register(
			function ( $class ) {
				$parts = explode( '\\', $class );
				$parts = array_map(
					function ( $part, $index ) {
						return ( $index < 2 ) ? strtolower( $part ) : $part;
					},
					$parts,
					array_keys( $parts )
				);

				$classname = implode( '\\', $parts );
				$filepath  = str_replace( '\\', '/', GRFM_PLUGINS . sprintf( '%s.php', $classname ) );

				if ( file_exists( $filepath ) ) {
					require_once $filepath;
				}
			}
		);

		//Api::create( sprintf( '%s%sapi', __DIR__, DIRECTORY_SEPARATOR ), '/api/' );
		Post\Type::register(
			key: 'documents',
			labelName: I18n::_t( 'Documentation' ),
			labelNamePlural: I18n::_t( 'Documentation' ),
			labelAllItems: I18n::_t( 'Documents' ),
			labelAdd: I18n::_t( 'Add Document' ),
			labelEdit: I18n::_t( 'Edit Document' ),
			labelUpdate: I18n::_t( 'Update Document' ),
			labelView: I18n::_t( 'View Document' ),
			labelSearch: I18n::_t( 'Search Documents' ),
			labelSave: I18n::_t( 'Save Document' ),
			public: true,
			hierarchical: false,
			searchable: false,
			showInMenu: true,
			showInBar: true,
			canExport: true,
			canImport: true,
			capabilities: ['types_edit'],
			menuIcon: 'ph ph-file-doc',
			menuPosition: 200,
		);

		Tree::attach( 'dashboard-main-menu', fn ( Tree $tree ) => $tree->addItems(
			[
				[
					'id'           => 'docify',
					'url'          => 'docify',
					'title'        => I18n::_t( 'Import project' ),
					'capabilities' => ['manage_options'],
					'icon'         => '',
					'position'     => 100,
					'parent_id'    => 'documents',
				],
			]
		) );

		/**
		 * Get all uploaded plugins.
		 *
		 * @since 2025.1
		 */
//		$plugins = new Plugins\Manager( function () {
//			$paths = ( new Dir( GRFM_PLUGINS ) )->getFiles( '*.php', 1 );
//			if ( ! $paths ) {
//				return null;
//			}
//		} );
//		if ( $plugins::$collection ) {
//			$docblock = ( new Parser() )->run( '' );
//			$classes  = ( new Finder() )->methods( $plugins::$collection );
//			echo '<pre>';
//			print_r( $classes );
//			echo '</pre>';
//		}
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
