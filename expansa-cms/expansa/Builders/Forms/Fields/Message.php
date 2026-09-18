<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/message.blade.php` - a static informational or warning message block.
 */
class Message extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'message',
            label: t('Message'),
            category: 'layout',
            icon: 'ph ph-info',
            description: t('A static informational or warning message block.'),
            defaults: [
                'label'       => '',
                'instruction' => '',
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Title')],
            ['type' => 'textarea', 'name' => 'instruction', 'label' => t('Message'), 'attributes' => ['required' => true]],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
