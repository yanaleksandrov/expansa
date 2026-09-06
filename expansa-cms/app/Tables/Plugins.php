<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Plugins extends Table
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

    public function cells(): array
    {
        return [
            $this->cell('cb')
                ->title('<input type="checkbox" u-bind="trigger" />')
                ->fixedWidth('1rem')
                ->view('cb'),
            $this->cell('image')
                ->fixedWidth('2.5rem')
                ->view('image'),
            $this->cell('plugin')
                ->title(t('Plugin'))
                ->flexibleWidth('14rem')
                ->view('plugin'),
            $this->cell('description')
                ->title(t('Description'))
                ->flexibleWidth('14rem')
                ->view('raw'),
            $this->cell('version')
                ->title(t('Version'))
                ->fixedWidth('4rem')
                ->view('badge'),
            $this->cell('active')
                ->title(t('Activity'))
                ->fixedWidth('4rem')
                ->view('toggle'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'   => t('Plugins'),
            'actions' => true,
            'filter'  => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'icon'        => 'no-plugins',
            'title'       => t('Plugins are not installed yet'),
            'description' => t('You can download them manually or install from the repository'),
        ];
    }
}
