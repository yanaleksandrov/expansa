<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/header.blade.php` - a section heading with an optional description.
 */
class Header extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'header',
            label: t('Heading'),
            category: 'layout',
            icon: 'ph ph-text-h',
            description: t('A section heading with an optional description, used to visually group fields.'),
            defaults: [
                'label'       => '',
                'instruction' => '',
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Heading'), 'attributes' => ['required' => true]],
            ['type' => 'text', 'name' => 'instruction', 'label' => t('Description')],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
