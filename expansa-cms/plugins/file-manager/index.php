<?php

declare(strict_types=1);

use App\Facades\Dashboard;
use Expansa\Builders\Tree;
use Expansa\Extensions\Plugin;
use Expansa\Facades\Asset;
use Expansa\Support\Is;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('File Manager')
            ->setVersion('2025.2')
            ->setAuthor('Expansa Team')
            ->setDescription(t('Edit, delete, upload, download, copy, and paste files and folders.'));
    }

    public function boot(): void
    {
        if (! Is::dashboard()) {
            return;
        }

        Dashboard::page('file-manager', view: __DIR__ . '/views/file-manager');

        Asset::style('file-manager', '/plugins/file-manager/assets/css/main.css');

        Tree::attach('dashboard-panel-menu', static fn (Tree $tree) => $tree->addItems(
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
    }

    public function deactivate(): void
    {
    }

    public function install(): void
    {
    }

    public function uninstall(): void
    {
    }
};
