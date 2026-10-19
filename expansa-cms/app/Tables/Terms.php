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
                'id'    => 1,
                'image' => url('dashboard/assets/images/placeholder.svg'),
                'title' => 'Hello World',
                'slug'  => 'hello-world',
                'count' => 23,
            ],
            [
                'id'    => 2,
                'image' => url('dashboard/assets/images/placeholder.svg'),
                'title' => 'Uncategorized',
                'slug'  => 'uncategorized',
                'count' => 5,
            ],
        ];
    }

    public function cells(): array
    {
        return [
            $this->cell('id')
                ->title('<input type="checkbox" u-bind="trigger">')
                ->fixedWidth('1.125rem')
                ->view('cb'),
            $this->cell('image')
                ->title(t('Image'))
                ->fixedWidth('2rem')
                ->view('image'),
            $this->cell('title')
                ->title(t('Title'))
                ->view('title'),
            $this->cell('slug')
                ->title(t('Slug'))
                ->view('raw'),
            $this->cell('count')
                ->title(t('Count'))
                ->fixedWidth('2rem')
                ->view('raw'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'   => t('Terms'),
            'actions' => 'posts-actions',
            'filter'  => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('No pages found'),
            'description' => t("You don't have any pages yet. Add them manually or import them from a CSV file."),
            'buttons'     => [
                [
                    'label' => t('Import'),
                    'url'   => url('/dashboard/import'),
                ],
            ],
        ];
    }
}
