<?php

declare(strict_types=1);

namespace App\Api\Extensions;

/**
 * Hardcoded mock data for the extensions marketplace UI prototype — moved verbatim
 * from the old App\Api\Extensions::get(), nothing real to fix here.
 */
final class ExtensionsService
{
    public function list(): array
    {
        return [
            'items' => [
                [
                    'title'           => 'Classic Editor 1 and very longadable',
                    'description'     => 'Customize WordPress with powerful, professional and intuitive fields.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Active',
                            'class' => 'bg-green-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'Classic Editor 2',
                    'description'     => 'Universal tool for managing the WordPress theme customizer, metaboxes and custom fields.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => '$9.99',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Active',
                            'class' => 'bg-green-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'Classic Editor 3',
                    'description'     => 'Convert Non-Latin characters in post and term slugs to Latin characters. Useful for creating human-readable URLs.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installed'       => true,
                    'active'          => true,
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Active',
                            'class' => 'bg-green-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'Classic Editor 4',
                    'description'     => 'Envato Theme Check is a modified fork of the original Theme Check by Otto42 with additional Themeforest specific WordPress checks.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Active',
                            'class' => 'bg-green-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'Classic Editor 2',
                    'description'     => 'The best anti-spam protection to block spam comments and spam in a contact form. The most trusted antispam solution.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Draft',
                            'class' => 'bg-blue-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'Query Monitor',
                    'description'     => 'The developer tools panel for Expansa.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Draft',
                            'class' => 'bg-blue-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'SVG Support',
                    'description'     => 'Upload SVG files to the Media Library and render SVG files inline for direct styling/animation of an SVG\'s internal elements using CSS/JS.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Draft',
                            'class' => 'bg-blue-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'Classic Editor',
                    'description'     => 'The best anti-spam protection to block spam comments and spam in a contact form.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Draft',
                            'class' => 'bg-blue-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'eCommerce',
                    'description'     => 'An ecommerce toolkit that helps you sell anything. Beautifully.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Install',
                            'class' => 'bg-red-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
                [
                    'title'           => 'WP Reset',
                    'description'     => 'Reset the entire site or just selected parts while reserving the option to undo by using snapshots.',
                    'image'           => url('dashboard/assets/images/placeholder.svg'),
                    'license'         => 'Free',
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
                    'installations'   => '5+ Million Installations',
                    'status'          => [
                        [
                            'title' => 'Install',
                            'class' => 'bg-red-lt',
                        ],
                    ],
                    'date'            => '2023/09/18 at 10:13 am',
                    'reviews'         => 23,
                    'rating'          => 4,
                    'expansa_version' => 1,
                    'version'         => '1.3.5',
                ],
            ],
        ];
    }
}
