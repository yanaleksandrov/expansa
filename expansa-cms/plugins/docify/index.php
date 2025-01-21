<?php

declare(strict_types=1);

use app\Post;
use app\Tree;
use Expansa\Extensions\Plugin;
use Expansa\I18n;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('Docify')
            ->setVersion('2024.9')
            ->setAuthor('Expansa Team')
            ->setDescription(I18n::_t('Simple way to create docs for your plugins'));
    }

    public function boot(): void
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
                $filepath  = str_replace( '\\', '/', EX_PLUGINS . sprintf( '%s.php', $classname ) );

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
//			$paths = ( new Dir( EX_PLUGINS ) )->getFiles( '*.php', 1 );
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

    public function activate(): void
    {
        // TODO: Implement activate() method.
    }

    public function deactivate(): void
    {
        // TODO: Implement deactivate() method.
    }

    public function install(): void
    {
        // TODO: Implement install() method.
    }

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
};
