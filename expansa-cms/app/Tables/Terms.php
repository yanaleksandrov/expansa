<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;
use Expansa\Builders\Table\Values\Code;
use Expansa\Builders\Table\Values\Link;
use Expansa\Builders\Table\Values\Title;

final class Terms extends Table
{
    public function data(): array
    {
        return [
            [
                'id'    => 1,
                'image' => url('dashboard/assets/images/placeholder.svg'),
                'title' => new Title('Hello World'),
                'slug'  => new Code('hello-world'),
                'count' => new Link('23'),
            ],
            [
                'id'    => 2,
                'image' => url('dashboard/assets/images/placeholder.svg'),
                'title' => new Title('Uncategorized'),
                'slug'  => new Code('uncategorized'),
                'count' => new Link('5'),
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
                ->view('code'),
            $this->cell('count')
                ->title(t('Count'))
                ->fixedWidth('2rem')
                ->view('count'),
        ];
    }

    public function headData(): array
    {
        return [
            'title' => t('Terms'),
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('No pages found'),
            'description' => t("You don't have any pages yet. Add them manually or import them from a CSV file."),
            'buttons'     => [
                new Link(
                    title: t('Import'),
                    url: url('/dashboard/import'),
                ),
            ],
        ];
    }
}
