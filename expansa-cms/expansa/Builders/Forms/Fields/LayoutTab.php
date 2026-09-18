<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/layout-tab.blade.php` - a single tab panel of a tabbed form,
 * paired with {@see LayoutTabMenu} for its navigation.
 */
class LayoutTab extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'layout-tab',
            label: t('Tab'),
            category: 'layout',
            icon: 'ph ph-tabs',
            description: t('A single tab panel of a tabbed form.'),
            defaults: [
                'name'        => '',
                'label'       => '',
                'instruction' => '',
                'fields'      => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Tab label'), 'attributes' => ['required' => true]],
            ['type' => 'text', 'name' => 'icon', 'label' => t('Icon')],
            ['type' => 'text', 'name' => 'instruction', 'label' => t('Description')],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
