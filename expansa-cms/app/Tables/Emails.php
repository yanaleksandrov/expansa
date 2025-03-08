<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Emails extends Table
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

    public function cells(): array
    {
        return [
            $this->cell('cb')
                ->title('<input type="checkbox" x-bind="trigger" />')
                ->fixedWidth('1rem')
                ->view('cb'),
            $this->cell('title')
                ->title(t('Name'))
                ->flexibleWidth('15rem')
                ->sortable()
                ->view('title'),
            $this->cell('recipients')
                ->title(t('Recipients'))
                ->flexibleWidth('15rem')
                ->view('title'),
            $this->cell('event')
                ->title(t('Event'))
                ->fixedWidth('9rem')
                ->view('date'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'   => t('Emails'),
            'actions' => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('No emails templates found'),
            'description' => t('Add [new email template](:emailDialog) manually', url('/dashboard/import')),
        ];
    }
}
