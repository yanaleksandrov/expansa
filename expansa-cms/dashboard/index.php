<?php

namespace Dashboard;

use App\Http\VerifyCsrfToken;
use App\Models\User;
use App\Query\Query;
use App\Support\DashboardFavicons;
use Expansa\Assets\Manager;
use Expansa\Builders\Tree;
use Expansa\Database\FieldEav;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Facades\Safe;
use Expansa\Support\Is;

new class
{
    public function __construct()
    {
        if (!defined('EX_IS_DASHBOARD')) {
            define('EX_IS_DASHBOARD', true);
        }

        VerifyCsrfToken::seed();

        DashboardFavicons::enqueue();

        /**
         * Include CSS styles & JS scripts.
         *
         * @since 2025.1
         */
        $suffix = ! Is::debug() ? '.min' : '';
        $styles = [
            'phosphor', 'expansa', 'dialog', 'controls', 'utility', 'notifications', 'nav-editor', 'chat',
        ];
        foreach ($styles as $style) {
            Asset::style($style, url("/dashboard/assets/css/$style$suffix.css"));
        }

        /**
         * Auto-connect vendor JS per form field, so it loads only on pages that render that
         * field instead of on every dashboard page. Everything else keeps the default
         * co-located convention. date/range/color share form/input.blade.php, so Field::parse()
         * passes the original subtype as $context['type'] to tell them apart.
         *
         * @since 2025.1
         */
        Manager::configure(function (string $file, array $context = []) use ($suffix): array {
            if (! str_contains(str_replace('\\', '/', $file), '/dashboard/views/form/')) {
                return Manager::defaultStructure($file);
            }

            $template      = basename($file, '.blade.php');
            $inputType     = $context['type'] ?? '';
            $dateTimeTypes = ['date', 'datetime-local', 'time', 'month', 'week'];

            $vendor = match (true) {
                $template === 'select'                           => 'youla-select',
                $inputType === 'color'                           => 'youla-filler',
                in_array($inputType, $dateTimeTypes, true) => 'youla-pickadate',
                $inputType === 'range'                           => 'youla-ranger',
                default                                          => null,
            };

            return $vendor === null
                ? Manager::defaultStructure($file)
                : ['js' => EX_PATH . "dashboard/assets/js/$vendor$suffix.js"];
        });

        $user   = User::current();
        $userId = $user->id ?? 0;

        // "youla-storage" (the media library data provider/dialog) is global, not co-located
        // per-page, because it must be reachable from any field on any page - see
        // src/js/youla-storage.js and views/dialogs/media-library.blade.php.
        $scripts = ['youla', 'youla-ajax', 'youla-expansa', 'youla-chat', 'youla-storage'];
        foreach ($scripts as $script) {
            $data = [];
            if ($script === 'youla') {
                $data['data'] = Hook::call(
                    'expansa_dashboard_data',
                    [
                        'apiurl'              => url('/api/'),
                        'apiKeys'             => Query::apply(
                            [
                                'type'      => 'api-keys',
                                'per_page'  => 25,
                                'author_id' => $userId,
                            ],
                            function ($query, $items) {
                                $posts = [];

                                foreach ($items as $i => $item) {
                                    foreach ((array) $item as $key => $value) {
                                        if (!in_array($key, ['uuid', 'title', 'status', 'createdAt', 'updatedAt'], true)) {
                                            continue;
                                        }

                                        if (in_array($key, ['createdAt', 'updatedAt'], true)) {
                                            $date = new \DateTime($value);
                                            if ($date instanceof \DateTime) {
                                                $value = $date->format('j F, Y');
                                            }
                                        }

                                        $posts[$i][$key] = $value;
                                    }

                                    $fields = new FieldEav($item)->find();
                                    if ($fields) {
                                        foreach ($fields as $field => $values) {
                                            $key = Safe::camelcase($field);
                                            if (!isset($key, $values[0])) {
                                                continue;
                                            }

                                            if (in_array($key, ['endDate', 'startDate'], true)) {
                                                $date = \DateTime::createFromFormat('Y-m-d', $values[0]);
                                                if ($date instanceof \DateTime) {
                                                    $values[0] = $date->format('j F, Y');
                                                }
                                            }

                                            $posts[$i][$key] = $values[0];
                                        }
                                    }
                                }

                                return $posts;
                            }
                        ),
                        'items'               => [],
                        'locale'              => I18n::locale(),
                        'dateFormat'          => 'd MMMM, yyyy',
                        'datepicker'          => [
                            'days'        => [
                                t('Sunday'),
                                t('Monday'),
                                t('Tuesday'),
                                t('Wednesday'),
                                t('Thursday'),
                                t('Friday'),
                                t('Saturday')
                            ],
                            'daysShort'   => [
                                t('Sun'),
                                t('Mon'),
                                t('Tue'),
                                t('Wed'),
                                t('Thu'),
                                t('Fri'),
                                t('Sat')
                            ],
                            'daysMin'     => [
                                t('Su'),
                                t('Mo'),
                                t('Tu'),
                                t('We'),
                                t('Th'),
                                t('Fr'),
                                t('Sa')
                            ],
                            'months'      => [
                                t('January'),
                                t('February'),
                                t('March'),
                                t('April'),
                                t('May'),
                                t('June'),
                                t('July'),
                                t('August'),
                                t('September'),
                                t('October'),
                                t('November'),
                                t('December')
                            ],
                            'monthsShort' => [
                                t('Jan'),
                                t('Feb'),
                                t('Mar'),
                                t('Apr'),
                                t('May'),
                                t('Jun'),
                                t('Jul'),
                                t('Aug'),
                                t('Sep'),
                                t('Oct'),
                                t('Nov'),
                                t('Dec')
                            ],
                            'today'       => t('Today'),
                            'clear'       => t('Clear'),
                            'dateFormat'  => 'MM/dd/yyyy',
                            'timeFormat'  => 'hh:mm aa',
                            'firstDay'    => 0,
                        ],
                        'weekStart'           => 1,
                        'loadingText'         => t('Loading...'),
                        'noResultsText'       => t('No results found'),
                        'noChoicesText'       => t('No choices to choose from'),
                        'uniqueItemText'      => t('Only unique values can be added'),
                        'customAddItemText'   => t('Only values matching specific conditions can be added'),
                        'showFilter'          => false,
                        'bulk'                => false,
                        'showMenu'            => false,
                        'flagsUrl'            => url('/dashboard/assets/sprites/flags.svg'),
                        'notifications'       => [
                            'ctrlS' => t_attr('Expansa saves the changes automatically, so there is no need to press ⌘ + S'),
                        ],
                        'uploaderDialog'      => [
                            'title' => t('Upload Files'),
                            'class' => 'dialog--md',
                        ],
                        'emailDialog'         => [
                            'title' => t('Email Settings'),
                            'class' => 'dialog--xl dialog--right',
                        ],
                        'postEditorDialog'    => [
                            'title' => t('Post Editor'),
                            'class' => 'dialog--lg dialog--right',
                        ],
                        'takeSelfieDialog'    => [
                            'title' => t('Take A Selfie'),
                            'class' => 'dialog--sm',
                        ],
                        'apiKeyManagerDialog' => [
                            'title' => t('Create/update API key'),
                            'class' => 'dialog--sm',
                        ],
                        'mediaLibraryDialog'  => [
                            'title' => t('Media Library'),
                            'class' => 'dialog--xl',
                        ],
                    ]
                );
            }
            Asset::script($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
        }

        /**
         * Register menu
         *
         * @since 2025.1
         */
        Tree::attach('dashboard-panel-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'           => 'users',
                    'url'          => 'users',
                    'title'        => t('Users'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-users-three',
                    'position'     => 600,
                ],
                [
                    'id'           => 'emails',
                    'url'          => "edit?table=email",
                    'title'        => t('Emails'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-mailbox',
                    'position'     => 700,
                ],
                [
                    'id'           => 'tasks',
                    'url'          => 'tasks',
                    'title'        => t('My plans and tasks'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-list-checks',
                    'position'     => 800,
                ],
                [
                    'id'           => 'settings',
                    'url'          => 'settings',
                    'title'        => t('Settings'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-gear',
                    'position'     => 900,
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
                    'title'        => t('Sign out'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-sign-out',
                    'position'     => 100,
                ],
                [
                    'id'       => 'divider-content',
                    'title'    => '',
                    'position' => 200,
                ],
                [
                    'id'           => 'profile',
                    'url'          => 'profile',
                    'title'        => t('Profile'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-gear',
                    'position'     => 300,
                ],
                [
                    'id'       => 'divider-content',
                    'title'    => '',
                    'position' => 400,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'comments',
                    'title'        => t('Add account'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-user-plus',
                    'position'     => 500,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'comments',
                    'title'        => t('Igor Ivanov'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-user-plus',
                    'position'     => 600,
                ],
            ]
        ));

        /**
         * Register menu in bar.
         *
         * @since 2025.1
         */
        Tree::attach('dashboard-menu-bar', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'           => 'website',
                    'url'          => '/',
                    'title'        => t('Expansa'),
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
                    'title'        => t('New'),
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
        ));

        /**
         * Register menu in left panel.
         *
         * @since 2025.1
         */
        Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'       => 'divider-workspace',
                    'title'    => t('Workspace'),
                    'position' => -20,
                ],
                [
                    'id'           => 'chat',
                    'url'          => 'chat',
                    'title'        => t('Chat'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-chat-circle-text',
                    'position'     => -10,
                ],
                [
                    'id'       => 'divider-content',
                    'title'    => t('Content'),
                    'position' => 10,
                ],
                [
                    'id'           => 'dialogs',
                    'url'          => 'comments',
                    'title'        => t('Discussions'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-chats',
                    'position'     => 200,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'edit?table=comments',
                    'title'        => t('Comments'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'dialogs',
                ],
                [
                    'id'           => 'field-groups',
                    'url'          => 'field-groups',
                    'title'        => t('Custom Fields'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-stack',
                    'position'     => 250,
                ],
                [
                    'id'       => 'divider-customization',
                    'title'    => t('Customization'),
                    'position' => 300,
                ],
                [
                    'id'           => 'appearance',
                    'url'          => 'themes',
                    'title'        => t('Appearance'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-paint-bucket',
                    'position'     => 400,
                ],
                [
                    'id'           => 'themes',
                    'url'          => 'themes',
                    'title'        => t('Themes'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'appearance',
                ],
                [
                    'id'           => 'menus',
                    'url'          => 'nav-menu',
                    'title'        => t('Menus'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'appearance',
                ],
                [
                    'id'           => 'plugins',
                    'url'          => 'plugins',
                    'title'        => t('Plugins'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-plug',
                    'position'     => 500,
                ],
                [
                    'id'           => 'installed',
                    'url'          => 'plugins',
                    'title'        => t('Installed'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'plugins',
                ],
                [
                    'id'           => 'install',
                    'url'          => 'plugins-install',
                    'title'        => t('Add new'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'plugins',
                ],
                [
                    'id'           => 'tools',
                    'url'          => 'tools',
                    'title'        => t('Tools'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-hammer',
                    'position'     => 700,
                ],
                [
                    'id'           => 'import',
                    'url'          => 'import',
                    'title'        => t('Import'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'tools',
                ],
                [
                    'id'           => 'export',
                    'url'          => 'export',
                    'title'        => t('Export'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'tools',
                ],
                [
                    'id'           => 'translation',
                    'url'          => 'edit?table=translation',
                    'title'        => t('Multilingual'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-translate',
                    'position'     => 800,
                ],
                [
                    'id'           => 'translations',
                    'url'          => 'edit?table=translation',
                    'title'        => t('Translations'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'translation',
                ],
                [
                    'id'           => 'translations-settings',
                    'url'          => 'multilingual-settings',
                    'title'        => t('Settings'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 30,
                    'parent_id'    => 'translation',
                ],
            ]
        ));
    }
};
