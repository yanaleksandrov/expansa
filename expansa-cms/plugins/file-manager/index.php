<?php

declare(strict_types=1);

use Expansa\Builders\Tree;
use Expansa\Extensions\Plugin;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Is;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('File Manager')
            ->setVersion('2025.2')
            ->setAuthor('Expansa Team')
            ->setDescription(t('Tool for ability to edit, delete, upload, download, copy and paste files and folders.'));
    }

    public function boot(): void
    {
        if (! Is::dashboard()) {
            return;
        }

        // TODO: переделать подключение файлов плагинов
        Hook::add('expansa_view_part', function ($filepath) {
            if ($filepath === EX_DASHBOARD . 'views/file-manager.php') {
                $filepath = __DIR__ . '/views/file-manager.php';
            }
            return $filepath;
        });

        Asset::enqueue('file-manager', '/plugins/file-manager/assets/css/main.css');

        Tree::attach('dashboard-panel-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'           => 'file-manager',
                    'url'          => 'file-manager',
                    'title'        => t('File Manager'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-folder-open',
                    'position'     => 800,
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
