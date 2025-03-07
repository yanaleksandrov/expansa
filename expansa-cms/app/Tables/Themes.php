<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Themes extends Table
{
    public function data(): array
    {
        return [
            [
                'title'       => 'Rgbcode',
                'description' => 'Multipurpose theme for blog, startup, portfolio, business & e-commerce.',
                'screenshot'  => 'https://dev.codyshop.ru/wp-content/themes/rgbcode/screenshot.png',
                'reviews'     => 973,
                'version'     => '2025.1',
                'rating'      => 3.75,
                'installed'   => true,
            ],
            [
                'title'       => 'Daria',
                'description' => 'Multipurpose theme for blog, startup, portfolio, business & e-commerce.',
                'screenshot'  => 'https://dev.codyshop.ru/wp-content/themes/daria/screenshot.jpg',
                'reviews'     => 111,
                'version'     => '2025.1',
                'rating'      => 4.5,
                'installed'   => false,
            ],
            [
                'title'       => 'Twenty Twenty-Two',
                'description' => 'Multipurpose theme for blog, startup, portfolio, business & e-commerce.',
                'screenshot'  => '//ts.w.org/wp-content/themes/twentytwentytwo/screenshot.png',
                'reviews'     => 200,
                'version'     => '2025.1',
                'rating'      => 5,
                'installed'   => false,
            ],
            [
                'title'       => 'Lemony Health',
                'description' => 'Lemony Health is multipurpose eCommerce theme for any goals.',
                'screenshot'  => '//ts.w.org/wp-content/themes/lemmony/screenshot.png',
                'reviews'     => 0,
                'version'     => '2025.1',
                'rating'      => 0,
                'installed'   => false,
            ],
            [
                'title'       => 'Threaders',
                'description' => 'Threaders is a light and elegant free eCommerce Expansa block theme.',
                'screenshot'  => '//i0.wp.com/themes.svn.wordpress.org/twentytwentyfour/1.2/screenshot.png',
                'reviews'     => 973,
                'version'     => '2025.1',
                'rating'      => 3.75,
                'installed'   => true,
            ],
        ];
    }

    public function columns(): array
    {
        return [
            $this->cell('theme')->view('theme'),
        ];
    }
}
