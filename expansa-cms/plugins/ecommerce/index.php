<?php

declare(strict_types=1);

use app\Post\Type;
use app\Tree;
use Expansa\Asset;
use Expansa\Extensions\Plugin;
use Expansa\Hook;
use Expansa\Is;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('eCommerce')
            ->setVersion('2025.2')
            ->setAuthor('Expansa Team')
            ->setDescription(t('Everything you need to launch an online store in days and keep it growing for years.'));
    }

    public function boot(): void
    {
        if (! Is::dashboard()) {
            return;
        }

        // TODO: переделать подключение файлов плагинов
        Hook::add('expansa_view_part', function ($filepath) {
            if ($filepath === EX_DASHBOARD . 'views/order.php') {
                $filepath = __DIR__ . '/views/order.php';
            }
            if ($filepath === EX_DASHBOARD . 'views/orders.php') {
                $filepath = __DIR__ . '/views/orders.php';
            }
            if ($filepath === EX_DASHBOARD . 'views/categories.php') {
                $filepath = __DIR__ . '/views/categories.php';
            }
            if ($filepath === EX_DASHBOARD . 'views/attributes.php') {
                $filepath = __DIR__ . '/views/attributes.php';
            }
            if ($filepath === EX_DASHBOARD . 'views/attribute-editor.php') {
                $filepath = __DIR__ . '/views/attribute-editor.php';
            }
            return $filepath;
        });

        Asset::enqueue('ecommerce-main', '/plugins/ecommerce/assets/css/main.css');
        Asset::enqueue('ecommerce-order', '/plugins/ecommerce/assets/css/order.css');
        Asset::enqueue('ecommerce-notes', '/plugins/ecommerce/assets/css/notes.css');
        Asset::enqueue('ecommerce-product', '/plugins/ecommerce/assets/css/product.css');

        Type::register(
            key: 'orders',
            labelName: t('Order'),
            labelNamePlural: t('Orders'),
            labelAllItems: t('All Orders'),
            labelAdd: t('Add New'),
            labelEdit: t('Edit Order'),
            labelUpdate: t('Update Order'),
            labelView: t('View Order'),
            labelSearch: t('Search Orders'),
            labelSave: t('Save Order'),
            public: true,
            hierarchical: false,
            searchable: false,
            showInMenu: true,
            showInBar: true,
            canExport: true,
            canImport: true,
            capabilities: ['types_edit'],
            menuIcon: 'ph ph-shopping-bag',
            menuPosition: 270,
        );

        Type::register(
            key: 'products',
            labelName: t('Product'),
            labelNamePlural: t('Products'),
            labelAllItems: t('All Products'),
            labelAdd: t('Add New'),
            labelEdit: t('Edit Product'),
            labelUpdate: t('Update Product'),
            labelView: t('View Product'),
            labelSearch: t('Search Products'),
            labelSave: t('Save Product'),
            public: true,
            hierarchical: false,
            searchable: true,
            showInMenu: true,
            showInBar: true,
            canExport: true,
            canImport: true,
            capabilities: ['types_edit'],
            menuIcon: 'ph ph-shopping-cart',
            menuPosition: 280,
        );

        Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'       => 'divider-ecommerce',
                    'title'    => t('E-Commerce'),
                    'position' => 250,
                ],
                [
                    'id'           => 'ecommerce',
                    'url'          => 'ecommerce',
                    'title'        => t('Store'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-storefront',
                    'position'     => 280,
                    'count'        => 5,
                ],
                [
                    'id'           => 'settings',
                    'url'          => 'settings',
                    'title'        => t('Settings'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'ecommerce',
                ],
                [
                    'id'           => 'coupons',
                    'url'          => 'coupons',
                    'title'        => t('Coupons'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 5,
                    'parent_id'    => 'ecommerce',
                ],
                [
                    'id'           => 'status',
                    'url'          => 'status',
                    'title'        => t('Status'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'ecommerce',
                ],
                [
                    'id'           => 'categories',
                    'url'          => 'categories',
                    'title'        => t('Categories'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'products',
                ],
                [
                    'id'           => 'attributes',
                    'url'          => 'attributes',
                    'title'        => t('Attributes'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'products',
                ],
                [
                    'id'           => 'reviews',
                    'url'          => 'reviews',
                    'title'        => t('Reviews'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'products',
                ],
                [
                    'id'           => 'statuses',
                    'url'          => 'statuses',
                    'title'        => t('Statuses'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'orders',
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
