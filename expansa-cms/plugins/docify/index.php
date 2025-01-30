<?php

declare(strict_types=1);

use App\Post;
use Expansa\Builders\Tree;
use Expansa\Extensions\Plugin;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('Docify')
            ->setVersion('2024.9')
            ->setAuthor('Expansa Team')
            ->setDescription(t('Simple way to create docs for your plugins'));
    }

    public function boot(): void
    {
        spl_autoload_register(
            function ($class) {
                $parts = explode('\\', $class);
                $parts = array_map(
                    function ($part, $index) {
                        return ( $index < 2 ) ? strtolower($part) : $part;
                    },
                    $parts,
                    array_keys($parts)
                );

                $classname = implode('\\', $parts);
                $filepath  = str_replace('\\', '/', EX_PLUGINS . sprintf('%s.php', $classname));

                if (file_exists($filepath)) {
                    require_once $filepath;
                }
            }
        );

        Post\Type::register(
            key: 'documents',
            labelName: t('Documentation'),
            labelNamePlural: t('Documentation'),
            labelAllItems: t('Documents'),
            labelAdd: t('Add Document'),
            labelEdit: t('Edit Document'),
            labelUpdate: t('Update Document'),
            labelView: t('View Document'),
            labelSearch: t('Search Documents'),
            labelSave: t('Save Document'),
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

        Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'           => 'docify',
                    'url'          => 'docify',
                    'title'        => t('Import project'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 100,
                    'parent_id'    => 'documents',
                ],
            ]
        ));
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
