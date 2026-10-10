<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * Renders the `custom` template - developer-supplied markup via a `callback`
 * (callable or raw HTML string), for cases the field builder doesn't natively cover.
 */
final class Custom extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'custom',
            label: t('Custom HTML'),
            category: 'advanced',
            icon: 'ph ph-code',
            description: t('Renders developer-supplied HTML or a callback.'),
            defaults: [
                'callback' => null,
            ],
        );
    }

    /**
     * Not user-configurable: content is supplied by the developer via `callback`.
     */
    public function settings(): array
    {
        return [];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
