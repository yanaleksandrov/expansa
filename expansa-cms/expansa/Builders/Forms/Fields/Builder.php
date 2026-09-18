<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/builder.blade.php` - a visual rule builder for constructing
 * custom field location/visibility conditions (post type, status, role, ...).
 */
class Builder extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'builder',
            label: t('Rule Builder'),
            category: 'advanced',
            icon: 'ph ph-flow-arrow',
            description: t('A visual rule builder for constructing conditional field placement rules.'),
            defaults: [
                'name'  => '',
                'label' => '',
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Label')],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
