<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/select.blade.php` - a dropdown list, with support for `<optgroup>` via nested options.
 */
class Select extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'select',
            label: t('Select'),
            category: 'choice',
            icon: 'ph ph-list',
            description: t('A dropdown list for selecting one value.'),
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
