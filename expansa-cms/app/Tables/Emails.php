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
            'title'       => t('No email templates found'),
            'description' => t('Add a [new email template](:emailDialog) manually.', url('/dashboard/import')),
        ];
    }
}
