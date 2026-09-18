<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/checkbox.blade.php` - one checkbox, or a set of checkboxes when `options` is given.
 */
class Checkbox extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'checkbox',
            label: t('Checkbox'),
            category: 'choice',
            icon: 'ph ph-check-square',
            description: t('One or more checkboxes for boolean or multi-select values.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'options'    => [],
                'attributes' => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ...$this->baseSettings(),
            [
                'type'  => 'repeater',
                'name'  => 'options',
                'label' => t('Options'),
            ],
        ];
    }

    public function validate(array $field = []): array
    {
        $multiple = ! empty($field['options']);

        return [($field['name'] ?? '') => $this->withRequired($field, $multiple ? 'array' : 'accepted')];
    }
}
