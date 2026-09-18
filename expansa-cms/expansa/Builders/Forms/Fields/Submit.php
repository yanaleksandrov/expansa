<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/submit.blade.php` - a submit button.
 */
class Submit extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'submit',
            label: t('Submit'),
            category: 'basic',
            icon: 'ph ph-paper-plane-tilt',
            description: t('A submit button that sends the form.'),
            defaults: [
                'name'       => 'submit',
                'label'      => t('Submit'),
                'attributes' => ['type' => 'submit'],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Button text'), 'attributes' => ['required' => true]],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
