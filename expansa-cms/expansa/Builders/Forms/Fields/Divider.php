<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/divider.blade.php` - a plain horizontal divider, optionally labeled.
 */
class Divider extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'divider',
            label: t('Divider'),
            category: 'layout',
            icon: 'ph ph-minus',
            description: t('A horizontal divider used to visually separate sections of a form.'),
            defaults: [
                'label' => '',
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
