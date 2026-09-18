<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/layout-group.blade.php` - groups nested fields together in a responsive grid.
 * Nested fields are parsed into `$field['content']` by {@see \Expansa\Builders\Forms\Field::parse()}
 * before this class ever sees them.
 */
class LayoutGroup extends AbstractField
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
                'label'  => '',
                'fields' => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Label')],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
