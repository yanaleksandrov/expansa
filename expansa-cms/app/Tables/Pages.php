<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\User;
use App\Post\Status;
use Expansa\Builders\Table;

final class Pages extends Table
{
    public function data(): array
    {
        return [];
        return [
            [
                'id'         => 1,
                'image'      => url('dashboard/assets/images/placeholder.svg'),
                'title'      => 'Post title',
                'status'     => 'draft',
                'author_id'  => 1,
                'author'     => 'Yan Aleksandrov',
                'categories' => [],
                'date'       => '24 august 2024',
                'actions'    => [
                    [
                        'href'  => '#',
                        'class' => '#',
                        'title' => t('View'),
                    ],
                    [
                        'href'  => '#',
                        'class' => '#',
                        'title' => t('Duplicate'),
                    ],
                    [
                        'href'  => '#',
                        'class' => '#',
                        'title' => t('Delete'),
                    ],
                ],
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
        ];
    }

    public function filters(): array
    {
        return [
            'status'    => [
                'label'   => t('Status'),
                'icon'    => 'ph ph-circle-dashed',
                'options' => Status::getStatuses(),
            ],
            'author_id' => [
                'label'   => t('Author'),
                'icon'    => 'ph ph-user',
                'options' => User::options(),
            ],
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('No pages found'),
            'description' => t("You don't have any pages yet. Add them manually or import them from a CSV file."),
            'buttons'     => [
                [
                    'label' => '<i class="ph ph-plus"></i> ' . t('Add new'),
                    'url'   => url('/dashboard/import'),
                ],
                [
                    'label' => '<i class="ph ph-download-simple"></i> ' . t('Import from file'),
                    'url'   => url('/dashboard/import'),
                ],
            ],
        ];
    }
}
