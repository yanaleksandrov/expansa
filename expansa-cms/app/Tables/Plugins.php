<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table\Cell;
use Expansa\Builders\Table\Row;

final class Plugins
{
    public function data(): array
    {
        return [
            [
                'title'           => 'Classic Editor 1 and very longadable pluginsnameand hello world',
                'description'     => 'Customize WordPress with powerful, professional and intuitive fields.',
                'screenshot'      => 'https://ps.w.org/buddypress/assets/icon.svg',
                'author'          => [
                    [
                        'title' => 'Expansa Team',
                        'href'  => 'https://core.com',
                    ],
                ],
                'categories'      => [
                    [
                        'title' => 'Test category',
                        'href'  => 'https://core.com',
                    ],
                ],
                'installed'       => false,
                'active'          => false,
                'installations'   => '300k+ installations',
                'date'            => '18 September, 2024',
                'reviews'         => 23,
                'rating'          => 4,
                'expansa_version' => '2025.1',
                'version'         => '1.3.5',
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
            Cell::add('image')
                ->fixedWidth('2.5rem')
                ->view('image'),
            Cell::add('plugin')
                ->title(t('Plugin'))
                ->flexibleWidth('14rem')
                ->view('plugin'),
            Cell::add('description')
                ->title(t('Description'))
                ->flexibleWidth('14rem')
                ->view('raw'),
            Cell::add('version')
                ->title(t('Version'))
                ->fixedWidth('4rem')
                ->view('badge'),
            Cell::add('active')
                ->title(t('Activity'))
                ->fixedWidth('4rem')
                ->view('checkbox'),
        ];
    }

    public function attributes(): array
    {
        return [
            'class'  => 'table',
            'x-data' => 'table',
            'x-init' => '$ajax("extensions/get").then(response => console.log(response))',
        ];
    }

    public function headerContent(): array
    {
        return [
            'title'   => t('Plugins'),
            'actions' => true,
            'filter'  => true,
        ];
    }

    public function notFoundContent(): array
    {
        return [
            'icon'        => 'no-plugins',
            'title'       => t('Plugins are not installed yet'),
            'description' => t('You can download them manually or install from the repository'),
        ];
    }
}
