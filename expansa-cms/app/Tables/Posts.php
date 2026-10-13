<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Posts extends Table
{
    public function data(): array
    {
        return [
            [
                'id'         => 1,
                'image'      => url('dashboard/assets/images/placeholder.svg'),
                'title'      => 'Post title',
                'status'     => t('Draft'),
                'author'     => 'Yan Aleksandrov',
                'categories' => [],
                'date'       => '24 august 2024',
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
                ->fixedWidth('2.5rem')
                ->view('image'),
            $this->cell('title')
                ->title(t('Title'))
                ->flexibleWidth('16rem')
                ->sortable()
                ->view('title'),
            $this->cell('author')
                ->title(t('Author'))
                ->flexibleWidth('6rem')
                ->view('links'),
            $this->cell('categories')
                ->title(t('Categories'))
                ->flexibleWidth('6rem')
                ->view('links'),
            $this->cell('date')
                ->title(t('Date'))
                ->fixedWidth('6rem')
                ->sortable()
                ->view('date'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'   => t('Pages'),
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
