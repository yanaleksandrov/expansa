<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * Renders the `layout-tab-menu` template - the navigation menu for a set of
 * {@see LayoutTab} fields. Expects the full fields array under `fields`.
 */
final class LayoutTabMenu extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'layout-tab-menu',
            label: t('Tab Menu'),
            category: 'layout',
            icon: 'ph ph-list',
            description: t('Navigation menu rendered above a set of tab fields.'),
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
