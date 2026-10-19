<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * Renders the `layout-group` template - groups nested fields together in a responsive grid:
 * `layout` places the label, `columns` splits the fields.
 * Nested fields are parsed into `$field['content']` by {@see \Expansa\Builders\Form\Internal\Renderer::render()}
 * before this class ever sees them.
 */
final class LayoutGroup extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'layout-group',
            label: t('Group'),
            category: 'layout',
            icon: 'ph ph-squares-four',
            description: t('Groups nested fields together in a responsive grid.'),
            defaults: [
                'label'   => '',
                'layout'  => 'row',
                'columns' => 1,
                'fields'  => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            [
                'type'  => 'text',
                'name'  => 'label',
                'label' => t('Label'),
            ],
            [
                'type'    => 'select',
                'name'    => 'layout',
                'label'   => t('Layout'),
                'options' => [
                    'row'     => t('Label on the left'),
                    'compact' => t('Compact'),
                    'stack'   => t('Label on top'),
                    'plain'   => t('Without label column'),
                ],
            ],
            [
                'type'    => 'select',
                'name'    => 'columns',
                'label'   => t('Columns'),
                'options' => [
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                ],
            ],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
