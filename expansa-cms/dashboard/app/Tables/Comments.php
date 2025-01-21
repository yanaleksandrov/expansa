<?php

declare(strict_types=1);

namespace Dashboard\Tables;

use Dashboard\Table\Cell;
use Dashboard\Table\Row;

final class Comments
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

    public function rows(): array
    {
        return [
            Row::add()->attribute('class', 'table__row')
        ];
    }

    public function columns(): array
    {
        return [
            Cell::add('cb')
                ->title('<input type="checkbox" x-bind="trigger" />')
                ->fixedWidth('1rem')
                ->view('cb'),
            Cell::add('author')
                ->title(t('Author'))
                ->flexibleWidth('6rem')
                ->view('links'),
            Cell::add('comment')
                ->title(t('Comment'))
                ->flexibleWidth('6rem')
                ->view('raw'),
            Cell::add('date')
                ->title(t('In response to'))
                ->fixedWidth('9rem')
                ->sortable()
                ->view('date'),
            Cell::add('date')
                ->title(t('Date'))
                ->fixedWidth('9rem')
                ->sortable()
                ->view('date'),
        ];
    }

    public function attributes(): array
    {
        return [
            'class'  => 'table',
            'x-data' => 'table',
            'x-init' => '$ajax("posts/get").then(response => items = response.items)',
        ];
    }

    public function headerContent(): array
    {
        return [
            'title'   => t('Comments'),
            'actions' => true,
            'filter'  => true,
        ];
    }

    public function notFoundContent(): array
    {
        return [
            'title'       => t('No comments found'),
            'description' => t("Don't worry, they will appear as soon as someone leaves a comment."),
        ];
    }
}
