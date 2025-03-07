<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Terms extends Table
{
    public function data(): array
    {
        return [
            [
                'image' => 'https://dev.codyshop.ru/wp-content/themes/rgbcode/screenshot.png',
                'title' => 'Hello World',
                'slug'  => 'hello-world',
                'count' => 23,
            ],
            [
                'image' => 'https://dev.codyshop.ru/wp-content/themes/rgbcode/screenshot.png',
                'title' => 'Uncategorized',
                'slug'  => 'uncategorized',
                'count' => 5,
            ],
        ];
    }

    public function columns(): array
    {
        return [
            $this->cell('cb')->title('<input type="checkbox" x-bind="trigger" />')->fixedWidth('1rem')->view('cb'),
            $this->cell('image')->title(t('Image'))->fixedWidth('2.5rem')->view('image'),
            $this->cell('title')->title(t('Title'))->view('title'),
            $this->cell('slug')->title(t('Slug'))->view('raw'),
            $this->cell('count')->title(t('Count'))->fixedWidth('2rem')->view('raw'),
        ];
    }
}
