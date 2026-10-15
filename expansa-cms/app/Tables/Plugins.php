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
            $is_active = in_array($id, $active, true);

            $rows[] = [
                'id'          => $id,
                'title'       => $plugin->name,
                'description' => $plugin->description,
                'author'      => $plugin->author,
                'image'       => url('dashboard/assets/images/placeholder.svg'),
                'active'      => $is_active,
                'version'     => [
                    'class' => '',
                    'icon'  => '',
                    'value' => $plugin->version,
                ],
                'status'      => [
                    'class'      => 'badge' . ($is_active ? ' badge--green-lt' : ''),
                    'icon'       => 'ph' . ($is_active ? ' ph-check' : ' ph-circle-half'),
                    'value'      => $is_active ? t('Active') : t('Inactive'),
                    'attributes' => [
                        '@click' => $is_active ? "\$ajax.post('extensions/deactivate',{id: '$id'})" : "\$ajax.post('extensions/activate',{id: '$id'})",
                    ],
                ],
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
            $this->cell('image')
                ->fixedWidth('2.5rem')
                ->view('image'),
            $this->cell('plugin')
                ->title(t('Plugin'))
                ->flexibleWidth('14rem')
                ->view('plugin'),
            $this->cell('description')
                ->title(t('Description'))
                ->flexibleWidth('14rem'),
            $this->cell('version')
                ->title(t('Version'))
                ->fixedWidth('5rem')
                ->view('badge'),
            $this->cell('status')
                ->title(t('Status'))
                ->fixedWidth('5rem')
                ->view('badge'),
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
                    'label' => '<i class="ph ph-plus"></i> ' . t('Add New Plugin'),
                    'url'   => url('/dashboard/plugins-install'),
                ],
            ],
        ];
    }
}
