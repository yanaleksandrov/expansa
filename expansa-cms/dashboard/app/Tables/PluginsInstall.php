<?php

declare(strict_types=1);

namespace Dashboard\Tables;

use Dashboard\Table\Cell;
use Dashboard\Table\Row;

final class PluginsInstall
{
    public function data(): array
    {
        return [
            [
                'title'           => 'Classic Editor 1 and very longadable pluginsnameand hello world',
                'description'     => 'Customize WordPress with powerful, professional and intuitive fields.',
                'screenshot'      => 'https://ps.w.org/buddypress/assets/icon.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => false,
                'active'          => false,
                'installations'   => '300k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'Classic Editor 2',
                'description'     => 'Universal tool for managing the WordPress theme customizer, metaboxes and custom fields.',
                'screenshot'      => 'https://ps.w.org/performance-lab/assets/icon.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'My test link',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => false,
                'installations'   => '100k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'Classic Editor 3',
                'description'     => 'Convert Non-Latin characters in post and term slugs to Latin characters. Useful for creating human-readable URLs.',
                'screenshot'      => 'https://s.w.org/plugins/geopattern-icon/classic-widgets.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                    [
                        'title' => 'New link',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => false,
                'active'          => true,
                'installations'   => '100k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 0,
                'rating'          => 0,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'Classic Editor 4',
                'description'     => 'Envato Theme Check is a modified fork of the original Theme Check by Otto42 with additional Themeforest specific WordPress checks.',
                'screenshot'      => 'https://ps.w.org/woocommerce/assets/icon-256x256.gif',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => true,
                'installations'   => '10k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'Classic Editor 2',
                'description'     => 'The best anti-spam protection to block spam comments and spam in a contact form. The most trusted antispam solution.',
                'screenshot'      => 'https://ps.w.org/woocommerce-payments/assets/icon-256x256.png',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => false,
                'installations'   => '10k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'Query Monitor',
                'description'     => 'The developer tools panel for Expansa.',
                'screenshot'      => 'https://ps.w.org/customer-reviews-woocommerce/assets/icon-256x256.png',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => false,
                'installations'   => '10k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'SVG Support',
                'description'     => 'Upload SVG files to the Media Library and render SVG files inline for direct styling/animation of an SVG\'s internal elements using CSS/JS.',
                'screenshot'      => 'https://s.w.org/plugins/geopattern-icon/classic-widgets.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => false,
                'installations'   => '10k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'Classic Editor',
                'description'     => 'The best anti-spam protection to block spam comments and spam in a contact form.',
                'screenshot'      => 'https://s.w.org/plugins/geopattern-icon/classic-widgets.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => false,
                'installations'   => '10k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'eCommerce',
                'description'     => 'An ecommerce toolkit that helps you sell anything. Beautifully.',
                'screenshot'      => 'https://s.w.org/plugins/geopattern-icon/classic-widgets.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => false,
                'installations'   => '10k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
            [
                'title'           => 'WP Reset',
                'description'     => 'Reset the entire site or just selected parts while reserving the option to undo by using snapshots.',
                'screenshot'      => 'https://s.w.org/plugins/geopattern-icon/classic-widgets.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => true,
                'active'          => false,
                'installations'   => '10k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
            ],
        ];
    }

    public function dataVariable(): string
    {
        return '';
    }

    public function rows(): array
    {
        return [
            Row::add()->tag(''),
        ];
    }

    public function columns(): array
    {
        return [
            Cell::add('extension')->view('extension'),
        ];
    }

    public function attributes(): array
    {
        return [
            'class' => 'plugins',
        ];
    }

    public function notFoundContent(): array
    {
        return [
            'icon'        => 'no-plugins',
            'title'       => t('Plugins not found'),
            'description' => t('You don\'t have any themes installed yet, <a @click="$dialog.open(\'tmpl-post-editor\')">download them</a>'),
        ];
    }

    public function headerContent(): array
    {
        return [
            'title' => t('Add Plugins'),
        ];
    }

    public function headerTemplate(): string
    {
        return '';
    }
}
