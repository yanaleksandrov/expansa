<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/radio.blade.php` - a group of radio buttons for a single-value choice.
 */
class Radio extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'radio',
            label: t('Radio'),
            category: 'choice',
            icon: 'ph ph-radio-button',
            description: t('A group of radio buttons for selecting a single value.'),
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
        return [($field['name'] ?? '') => $this->withRequired($field)];
    }
}
