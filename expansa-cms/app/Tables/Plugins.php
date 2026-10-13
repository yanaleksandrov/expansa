<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Option;
use Expansa\Builders\Table;
use Expansa\Facades\Extensions;

final class Plugins extends Table
{
    /**
     * Installed plugins, the active ones as listed in the "extensions.active" option.
     *
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $active = (array) Option::get('extensions.active', []);

        $rows = [];
        foreach (Extensions::getInstalled('plugin') as $id => $plugin) {
            $rows[] = [
                'id'          => $id,
                'title'       => $plugin->name,
                'description' => $plugin->description,
                'author'      => $plugin->author,
                'image'       => url('dashboard/assets/images/placeholder.svg'),
                'version'     => $plugin->version,
                'active'      => in_array($id, $active, true),
            ];
        }

        return $rows;
    }

    public function cells(): array
    {
        return [
            $this->cell('id')
                ->title('<input type="checkbox" u-bind="trigger">')
                ->fixedWidth('1.125rem')
                ->view('cb'),
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
                ->attributes([
                    // a failed request turns the switch back
                    '@change' => '$ajax.post($event.target.checked ? "extensions/activate" : "extensions/deactivate",'
                        . ' {id: $event.target.value}).catch(() => $event.target.checked = !$event.target.checked)',
                ])
                ->view('toggle'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'   => t('Plugins'),
            'actions' => 'posts-actions',
            'filter'  => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'icon'        => 'no-plugins',
            'title'       => t('No plugins installed yet'),
            'description' => t('You can upload them manually or install them from the repository.'),
            'buttons'     => [
                [
                    'label' => t('Add New'),
                    'url'   => url('/dashboard/plugins-install'),
                ],
            ],
        ];
    }
}
