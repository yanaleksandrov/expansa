<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Option;
use Expansa\Builders\Table;
use Expansa\Builders\Table\Values\Badge;
use Expansa\Builders\Table\Values\Extension;
use Expansa\Builders\Table\Values\Link;
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
                'plugin'      => new Extension(
                    title: $plugin->name,
                    authors: $plugin->author !== '' ? [new Link($plugin->author)] : [],
                    version: $plugin->version,
                    installed: true,
                    active: $is_active,
                ),
                'description' => $plugin->description,
                'image'       => url('dashboard/assets/images/placeholder.svg'),
                'version'     => new Badge($plugin->version),
                'status'      => new Badge(
                    text: $is_active ? t('Active', context: 'plugin') : t('Inactive', context: 'plugin'),
                    class: 'badge' . ($is_active ? ' badge--green-lt' : ''),
                    icon: 'ph' . ($is_active ? ' ph-check' : ' ph-circle-half'),
                    attributes: self::action($is_active ? 'extensions/deactivate' : 'extensions/activate', ['id' => $id]),
                ),
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
                ->fixedWidth('2rem')
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
                ->fixedWidth('7rem')
                ->view('badge'),
        ];
    }

    public function headData(): array
    {
        return [
            'title' => t('Plugins'),
        ];
    }

    public function notFoundData(): array
    {
        return [
            'icon'        => 'no-plugins',
            'title'       => t('No plugins installed yet'),
            'description' => t('You can upload them manually or install them from the repository.'),
            'buttons'     => [
                new Link(
                    title: t('Add New Plugin'),
                    url: url('/dashboard/plugins-install'),
                    icon: 'ph ph-plus',
                ),
            ],
        ];
    }
}
