<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;
use Expansa\Builders\Table\Values\Extension;

final class Themes extends Table
{
    public function data(): array
    {
        return array_map(static fn (array $theme) => [
            'theme' => new Extension(
                title: $theme['title'],
                description: $theme['description'],
                screenshot: $theme['screenshot'],
                version: $theme['version'],
                installed: $theme['installed'],
                rating: (float) $theme['rating'],
                reviews: $theme['reviews'],
            ),
        ], [
            [
                'title'       => 'Rgbcode',
                'description' => 'Multipurpose theme for blog, startup, portfolio, business & e-commerce.',
                'screenshot'  => url('dashboard/assets/images/placeholder.svg'),
                'reviews'     => 973,
                'version'     => '2027.1',
                'rating'      => 3.75,
                'installed'   => true,
            ],
            [
                'title'       => 'Daria',
                'description' => 'Multipurpose theme for blog, startup, portfolio, business & e-commerce.',
                'screenshot'  => url('dashboard/assets/images/placeholder.svg'),
                'reviews'     => 111,
                'version'     => '2027.1',
                'rating'      => 4.5,
                'installed'   => false,
            ],
            [
                'title'       => 'Twenty Twenty-Two',
                'description' => 'Multipurpose theme for blog, startup, portfolio, business & e-commerce.',
                'screenshot'  => url('dashboard/assets/images/placeholder.svg'),
                'reviews'     => 200,
                'version'     => '2027.1',
                'rating'      => 5,
                'installed'   => false,
            ],
            [
                'title'       => 'Lemony Health',
                'description' => 'Lemony Health is multipurpose eCommerce theme for any goals.',
                'screenshot'  => url('dashboard/assets/images/placeholder.svg'),
                'reviews'     => 0,
                'version'     => '2027.1',
                'rating'      => 0,
                'installed'   => false,
            ],
            [
                'title'       => 'Threaders',
                'description' => 'Threaders is a light and elegant free eCommerce Expansa block theme.',
                'screenshot'  => '//i0.wp.com/themes.svn.wordpress.org/twentytwentyfour/1.2/screenshot.png',
                'reviews'     => 973,
                'version'     => '2027.1',
                'rating'      => 3.75,
                'installed'   => true,
            ],
        ]);
    }

    public function cells(): array
    {
        return [
            $this->cell('theme')->view('theme'),
        ];
    }

    /**
     * All themes on one page: the screen has no pagination.
     *
     * @return int
     */
    public function perPage(): int
    {
        return 0;
    }

    public function headData(): array
    {
        return [
            'title' => t('Themes'),
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('No themes found'),
            'description' => t("You don't have any themes installed yet."),
        ];
    }
}
