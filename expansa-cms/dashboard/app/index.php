<?php

namespace Dashboard;

use app\Tree;
use Expansa\Asset;
use Expansa\Hook;
use Expansa\I18n;
use Expansa\Is;
use Expansa\Url;

new class
{
    public function __construct()
    {
        if (!defined('EX_IS_DASHBOARD')) {
            define('EX_IS_DASHBOARD', true);
        }

        /**
         * Include CSS styles & JS scripts.
         *
         * @since 2025.1
         */
        $styles  = ['phosphor'];
        if (! Is::install()) {
            $styles  = [
                'phosphor', 'air-datepicker', 'colorist', 'datepicker', 'drooltip', 'slimselect', 'dialog', 'expansa', 'controls', 'utility', 'notifications', 'nav-editor',
            ];
        }
        foreach ($styles as $style) {
            if (! Is::debug()) {
                $style = sprintf('%s.min', $style);
            }
            Asset::enqueue($style, Url::dashboard('/assets/css/' . $style . '.css'));
        }

        $scripts = ['ajax', 'alpine'];
        if (! Is::install()) {
            $scripts = ['expansa', 'air-datepicker', 'notifications', 'ajax', 'datepicker', 'slimselect', 'drooltip', 'dragula', 'croppr', 'dialog', 'storage', 'alpine', 'sortable'];
        }

        foreach ($scripts as $script) {
            $data = [];
            if ($script === 'expansa') {
                $data['data'] = Hook::call(
                    'expansa_dashboard_data',
                    [
                        'apiurl'              => Url::site('/api/'),
                        'items'               => [],
                        'locale'              => I18n::locale(),
                        'dateFormat'          => 'j M, Y',
                        'weekStart'           => 1,
                        'showFilter'          => false,
                        'bulk'                => false,
                        'showMenu'            => false,
                        'spriteFlagsUrl'      => Url::site('/dashboard/assets/sprites/flags.svg'),
                        'notifications'       => [
                            'ctrlS' => I18n::_t_attr('Expansa saves the changes automatically, so there is no need to press ⌘ + S'),
                        ],
                        'uploaderDialog'      => [
                            'title' => I18n::_t('Upload Files'),
                            'class' => 'dialog--md',
                        ],
                        'emailDialog'         => [
                            'title' => I18n::_t('Email Settings'),
                            'class' => 'dialog--xl dialog--right',
                        ],
                        'postEditorDialog'    => [
                            'title' => I18n::_t('Post Editor'),
                            'class' => 'dialog--lg dialog--right',
                        ],
                        'takeSelfieDialog'    => [
                            'title' => I18n::_t('Take A Selfie'),
                            'class' => 'dialog--sm',
                        ],
                        'apiKeyManagerDialog' => [
                            'title' => I18n::_t('Create/update new project'),
                            'class' => 'dialog--sm',
                        ],
                    ]
                );
            }

            if (! Is::debug()) {
                $script = sprintf('%s.min', $script);
            }
            Asset::enqueue($script, Url::dashboard('/assets/js/' . $script . '.js'), $data);
        }

        /**
         * Register menu
         *
         * @since 2025.1
         */
        Tree::attach('core-panel-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'           => 'users',
                    'url'          => 'users',
                    'title'        => I18n::_t('Users'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-users-three',
                    'position'     => 600,
                ],
                [
                    'id'           => 'emails',
                    'url'          => 'emails',
                    'title'        => I18n::_t('Emails'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-mailbox',
                    'position'     => 700,
                ],
                [
                    'id'           => 'tasks',
                    'url'          => 'tasks',
                    'title'        => I18n::_t('My plans and tasks'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-list-checks',
                    'position'     => 800,
                ],
                [
                    'id'           => 'settings',
                    'url'          => 'settings',
                    'title'        => I18n::_t('Settings'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-gear',
                    'position'     => 900,
                ],
                [
                    'id'           => 'translation',
                    'url'          => 'translation',
                    'title'        => I18n::_t('Translation'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-translate',
                    'position'     => 1000,
                ],
            ]
        ));

        /**
         * Register menu for user.
         *
         * @since 2025.1
         */
        Tree::attach('dashboard-user-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'           => 'comments',
                    'url'          => 'comments',
                    'title'        => I18n::_t('Sign out'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-sign-out',
                    'position'     => 0,
                ],
                [
                    'id'       => 'divider-content',
                    'title'    => '',
                    'position' => 10,
                ],
                [
                    'id'           => 'profile',
                    'url'          => 'profile',
                    'title'        => I18n::_t('Profile'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-gear',
                    'position'     => 20,
                ],
                [
                    'id'           => 'profile',
                    'url'          => 'profile',
                    'title'        => I18n::_t('Set yourself as %saway%s', '<strong>', '</strong>'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-moon-stars',
                    'position'     => 30,
                ],
                [
                    'id'           => 'dialogs',
                    'url'          => 'comments',
                    'title'        => I18n::_t('Pause notifications'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-bell-slash',
                    'position'     => 40,
                ],
                [
                    'id'       => 'divider-content',
                    'title'    => '',
                    'position' => 50,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'comments',
                    'title'        => I18n::_t('Add account'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-user-plus',
                    'position'     => 60,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'comments',
                    'title'        => I18n::_t('Igor Ivanov'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-user-plus',
                    'position'     => 70,
                ],
            ]
        ));

        /**
         * Register menu in bar.
         *
         * @since 2025.1
         */
        Tree::attach('dashboard-menu-bar', function (Tree $tree) {
            $tree->addItems(
                [
                    [
                        'id'           => 'website',
                        'url'          => '/',
                        'title'        => I18n::_t('Expansa'),
                        'capabilities' => ['manage_options'],
                        'icon'         => 'ph ph-user-focus',
                        'position'     => 10,
                    ],
                    [
                        'id'           => 'updates',
                        'url'          => 'updates',
                        'title'        => 0,
                        'capabilities' => ['manage_options'],
                        'icon'         => 'ph ph-clock-clockwise',
                        'position'     => 20,
                    ],
                    [
                        'id'           => 'comments',
                        'url'          => 'comments',
                        'title'        => 0,
                        'capabilities' => ['manage_options'],
                        'icon'         => 'ph ph-chats',
                        'position'     => 30,
                    ],
                    [
                        'id'           => 'new',
                        'url'          => 'new',
                        'title'        => I18n::_t('New'),
                        'capabilities' => ['manage_options'],
                        'icon'         => 'ph ph-plus',
                        'position'     => 40,
                    ],
                    [
                        'id'           => 'site-health',
                        'url'          => 'site-health',
                        'title'        => '0Q 0.001s 999kb',
                        'capabilities' => ['manage_options'],
                        'icon'         => 'ph ph-monitor',
                        'position'     => 50,
                    ],
                ]
            );
        });

        /**
         * Register menu in left panel.
         *
         * @since 2025.1
         */
        Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'       => 'divider-workspace',
                    'title'    => I18n::_t('Workspace'),
                    'position' => 0,
                ],
                [
                    'id'           => 'profile',
                    'url'          => 'profile',
                    'title'        => I18n::_t('Profile'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-user-focus',
                    'position'     => 0,
                    'count'        => 5,
                ],
                [
                    'id'       => 'divider-content',
                    'title'    => I18n::_t('Content'),
                    'position' => 10,
                ],
                [
                    'id'           => 'dialogs',
                    'url'          => 'comments',
                    'title'        => I18n::_t('Discussions'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-chats',
                    'position'     => 200,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'comments',
                    'title'        => I18n::_t('Comments'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'dialogs',
                ],
                [
                    'id'           => 'chat',
                    'url'          => 'chat',
                    'title'        => I18n::_t('Chat'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'dialogs',
                ],
                [
                    'id'       => 'divider-customization',
                    'title'    => I18n::_t('Customization'),
                    'position' => 300,
                ],
                [
                    'id'           => 'appearance',
                    'url'          => 'themes',
                    'title'        => I18n::_t('Appearance'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-paint-bucket',
                    'position'     => 400,
                ],
                [
                    'id'           => 'themes',
                    'url'          => 'themes',
                    'title'        => I18n::_t('Themes'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'appearance',
                ],
                [
                    'id'           => 'menus',
                    'url'          => 'nav-menu',
                    'title'        => I18n::_t('Menus'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'appearance',
                ],
                [
                    'id'           => 'plugins',
                    'url'          => 'plugins',
                    'title'        => I18n::_t('Plugins'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-plug',
                    'position'     => 500,
                ],
                [
                    'id'           => 'installed',
                    'url'          => 'plugins',
                    'title'        => I18n::_t('Installed'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'plugins',
                ],
                [
                    'id'           => 'install',
                    'url'          => 'plugins-install',
                    'title'        => I18n::_t('Add new'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'plugins',
                ],
                [
                    'id'           => 'tools',
                    'url'          => 'tools',
                    'title'        => I18n::_t('Tools'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-hammer',
                    'position'     => 700,
                ],
                [
                    'id'           => 'import',
                    'url'          => 'import',
                    'title'        => I18n::_t('Import'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'tools',
                ],
                [
                    'id'           => 'export',
                    'url'          => 'export',
                    'title'        => I18n::_t('Export'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'tools',
                ],
            ]
        ));
    }
};
