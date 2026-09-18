<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/hidden.blade.php` - stores a fixed or computed value without displaying an input.
 */
class Hidden extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'hidden',
            label: t('Hidden'),
            category: 'advanced',
            icon: 'ph ph-eye-slash',
            description: t('Stores a fixed or computed value without displaying an input to the user.'),
            defaults: [
                'name'       => '',
                'attributes' => ['type' => 'hidden'],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'name', 'label' => t('Name'), 'attributes' => ['required' => true]],
            ['type' => 'text', 'name' => 'attributes.value', 'label' => t('Value')],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
