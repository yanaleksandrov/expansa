<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;
use Expansa\Facades\Form;

final class Pages extends Table
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
            'actions' => true,
            'filter'  => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('Pages not found'),
            'description' => t('You don&apos;t have any pages yet. <a @click="$dialog.open(`tmpl-post-editor`, postEditorDialog)">Add them manually</a> or [import via CSV](:importLink)', url('/dashboard/import')),
        ];
    }

    public function filter(): void
    {
        Form::override('items-filter', fn (\Expansa\Builders\Forms\Form $form) => $form->before('submit')->attach(
            [
                [
                    'type'        => 'select',
                    'name'        => 'authors',
                    'label'       => '',
                    'class'       => 'field field--sm field--outline',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [],
                    'options'     => [
                        ''                => t('Select an author'),
                        'user-registered' => t('New user registered'),
                    ],
                ],
                [
                    'type'        => 'date',
                    'name'        => 'date',
                    'label'       => '',
                    'class'       => 'field field--sm field--outline',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'readonly'    => true,
                        'placeholder' => t('Select dates'),
                    ],
                ],
            ]
        ));
    }
}
