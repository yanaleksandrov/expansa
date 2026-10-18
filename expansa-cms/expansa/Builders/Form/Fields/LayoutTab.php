<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * A single tab of a tabbed form: all tabs of a level render together through the `layout-tabs`
 * template, the menu and their panels.
 */
final class LayoutTab extends AbstractField
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
                'icon'        => '',
                'caption'     => '',
                'description' => '',
                'fields'      => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            [
                'type'       => 'text',
                'name'       => 'label',
                'label'      => t('Tab Label'),
                'attributes' => ['required' => true],
            ],
            [
                'type'  => 'text',
                'name'  => 'icon',
                'label' => t('Icon'),
            ],
            [
                'type'  => 'text',
                'name'  => 'caption',
                'label' => t('Caption'),
            ],
            [
                'type'  => 'text',
                'name'  => 'description',
                'label' => t('Description'),
            ],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
