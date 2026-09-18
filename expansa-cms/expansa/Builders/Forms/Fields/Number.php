<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/number.blade.php` - a numeric input with increment/decrement controls.
 */
class Number extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'number',
            label: t('Number'),
            category: 'basic',
            icon: 'ph ph-hash',
            description: t('A numeric input with increment/decrement controls.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'attributes' => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ...$this->baseSettings(),
            ['type' => 'number', 'name' => 'attributes.min', 'label' => t('Minimum value')],
            ['type' => 'number', 'name' => 'attributes.max', 'label' => t('Maximum value')],
            ['type' => 'number', 'name' => 'attributes.step', 'label' => t('Step')],
        ];
    }

    public function validate(array $field = []): array
    {
        $rules = array_filter([
            'numeric',
            isset($field['attributes']['min']) ? 'min:' . $field['attributes']['min'] : '',
            isset($field['attributes']['max']) ? 'max:' . $field['attributes']['max'] : '',
        ]);

        return [($field['name'] ?? '') => $this->withRequired($field, implode('|', $rules))];
    }
}
