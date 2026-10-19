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
                ->fixedWidth('1.125rem')
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
            'title' => t('Emails'),
            'find'  => t('Search emails'),
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('No email templates found'),
            'description' => t('Emails the site sends are made from these templates.'),
            'buttons'     => [
                [
                    'label' => t('Add Email Template'),
                    'click' => "\$dialog.open('tmpl-email-editor')",
                ],
            ],
        ];
    }
}
