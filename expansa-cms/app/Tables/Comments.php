<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Comments extends Table
{
    public function data(): array
    {
        return [
            [
                'cb'         => '<input type="checkbox" value="1">',
                'image'      => 'image',
                'title'      => 'Post title',
                'author'     => 'Yan Aleksandrov',
                'categories' => [],
                'date'       => '24 august 2024',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            $this->cell('cb')
                ->title('<input type="checkbox" x-bind="trigger" />')
                ->fixedWidth('1rem')
                ->view('cb'),
            $this->cell('author')
                ->title(t('Author'))
                ->flexibleWidth('6rem')
                ->view('links'),
            $this->cell('comment')
                ->title(t('Comment'))
                ->flexibleWidth('6rem')
                ->view('raw'),
            $this->cell('date')
                ->title(t('In response to'))
                ->fixedWidth('9rem')
                ->sortable()
                ->view('date'),
            $this->cell('date')
                ->title(t('Date'))
                ->fixedWidth('9rem')
                ->sortable()
                ->view('date'),
        ];
    }
}
