<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/input.blade.php`, shared by every plain HTML5 input subtype
 * (text, color, date, datetime-local, email, month, range, search, tel, time, url, week).
 * The concrete subtype is carried in `$field['attributes']['type']`, not on this class.
 */
class Input extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'input',
            label: t('Text'),
            category: 'basic',
            icon: 'ph ph-text-t',
            description: t('A basic single-line text input, useful for storing short string values.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'attributes' => ['type' => 'text'],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ...$this->baseSettings(),
            [
                'type'  => 'text',
                'name'  => 'attributes.placeholder',
                'label' => t('Placeholder'),
            ],
        ];
    }

    public function validate(array $field = []): array
    {
        $rule = match ($field['attributes']['type'] ?? 'text') {
            'email'                    => 'email',
            'url'                      => 'url',
            'range'                    => 'numeric',
            'date', 'datetime-local',
            'month', 'week', 'time'    => 'date',
            'color'                    => 'hex',
            default                    => '',
        };

        return [($field['name'] ?? '') => $this->withRequired($field, $rule)];
    }
}
