<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/layout-step.blade.php` - a single step of a multi-step (wizard) form.
 */
class LayoutStep extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'layout-step',
            label: t('Step'),
            category: 'layout',
            icon: 'ph ph-list-numbers',
            description: t('A single step of a multi-step (wizard) form.'),
            defaults: [
                'fields' => [],
            ],
        );
    }

    public function settings(): array
    {
        return [];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
