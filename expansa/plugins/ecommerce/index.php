<?php

use Expansa\Asset;
use Expansa\Hook;
use Expansa\I18n;
use Expansa\Is;
use Expansa\Tree;
use Expansa\Post\Type;
use Expansa\Extensions\Plugin;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('eCommerce')
            ->setVersion('2025.2')
            ->setAuthor('Expansa Team')
            ->setDescription(I18n::_t('Everything you need to launch an online store in days and keep it growing for years.'));
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
            labelName: I18n::_t('Order'),
            labelNamePlural: I18n::_t('Orders'),
            labelAllItems: I18n::_t('All Orders'),
            labelAdd: I18n::_t('Add New'),
            labelEdit: I18n::_t('Edit Order'),
            labelUpdate: I18n::_t('Update Order'),
            labelView: I18n::_t('View Order'),
            labelSearch: I18n::_t('Search Orders'),
            labelSave: I18n::_t('Save Order'),
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
            labelName: I18n::_t('Product'),
            labelNamePlural: I18n::_t('Products'),
            labelAllItems: I18n::_t('All Products'),
            labelAdd: I18n::_t('Add New'),
            labelEdit: I18n::_t('Edit Product'),
            labelUpdate: I18n::_t('Update Product'),
            labelView: I18n::_t('View Product'),
            labelSearch: I18n::_t('Search Products'),
            labelSave: I18n::_t('Save Product'),
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
                    'title'    => I18n::_t('E-Commerce'),
                    'position' => 250,
                ],
                [
                    'id'           => 'ecommerce',
                    'url'          => 'ecommerce',
                    'title'        => I18n::_t('Store'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-storefront',
                    'position'     => 280,
                    'count'        => 5,
                ],
                [
                    'id'           => 'settings',
                    'url'          => 'settings',
                    'title'        => I18n::_t('Settings'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'ecommerce',
                ],
                [
                    'id'           => 'coupons',
                    'url'          => 'coupons',
                    'title'        => I18n::_t('Coupons'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 5,
                    'parent_id'    => 'ecommerce',
                ],
                [
                    'id'           => 'status',
                    'url'          => 'status',
                    'title'        => I18n::_t('Status'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'ecommerce',
                ],
                [
                    'id'           => 'categories',
                    'url'          => 'categories',
                    'title'        => I18n::_t('Categories'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'products',
                ],
                [
                    'id'           => 'attributes',
                    'url'          => 'attributes',
                    'title'        => I18n::_t('Attributes'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'products',
                ],
                [
                    'id'           => 'reviews',
                    'url'          => 'reviews',
                    'title'        => I18n::_t('Reviews'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'products',
                ],
                [
                    'id'           => 'statuses',
                    'url'          => 'statuses',
                    'title'        => I18n::_t('Statuses'),
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
