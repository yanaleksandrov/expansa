<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * Renders the `textarea` template - a multi-line free text input.
 */
final class Textarea extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'textarea',
            label: t('Textarea'),
            category: 'basic',
            icon: 'ph ph-text-align-left',
            description: t('A multi-line free text input.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'attributes' => ['rows' => 4],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ...$this->baseSettings(),
            [
                'type'  => 'number',
                'name'  => 'attributes.rows',
                'error' => 'attributes.rows',
                'label' => t('Rows'),
            ],
            ...$this->lengthSettings(),
        ];
    }

    public function validate(array $field = []): array
    {
        return [($field['name'] ?? '') => $this->withLength($field)];
    }
}
