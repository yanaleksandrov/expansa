<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

use Expansa\Security\Sanitizer;

/**
 * Renders `form/textarea.blade.php` - a multi-line free text input.
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
                'label' => t('Rows'),
            ],
        ];
    }

    public function validate(array $field = []): array
    {
        $rule = match (true) {
            isset($field['attributes']['maxlength']) => 'lengthMax:' . Sanitizer::absint($field['attributes']['maxlength']),
            isset($field['attributes']['minlength']) => 'lengthMin:' . Sanitizer::absint($field['attributes']['minlength']),
            default                                  => '',
        };

        return [($field['name'] ?? '') => $this->withRequired($field, $rule)];
    }
}
