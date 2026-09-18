<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/details.blade.php` - a collapsible `<details>` block, typically used
 * to group optional or advanced content.
 */
class Details extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'details',
            label: t('Details'),
            category: 'layout',
            icon: 'ph ph-caret-circle-down',
            description: t('A collapsible block for grouping optional or advanced content.'),
            defaults: [
                'label'       => '',
                'instruction' => '',
                'content'     => '',
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Summary'), 'attributes' => ['required' => true]],
            ['type' => 'textarea', 'name' => 'instruction', 'label' => t('Description')],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
