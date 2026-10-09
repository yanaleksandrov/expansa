<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `components/form/progress.blade.php` - an animated progress bar indicator.
 */
final class Progress extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'progress',
            label: t('Progress'),
            category: 'basic',
            icon: 'ph ph-gauge',
            description: t('An animated progress bar indicator.'),
            defaults: [
                'label' => '',
                'min'   => 0,
                'max'   => 0,
                'value' => 100,
                'speed' => 1000,
            ],
        );
    }

    public function settings(): array
    {
        return [
            ['type' => 'text', 'name' => 'label', 'label' => t('Label')],
            ['type' => 'number', 'name' => 'min', 'label' => t('Minimum Value')],
            ['type' => 'number', 'name' => 'max', 'label' => t('Maximum Value')],
            ['type' => 'number', 'name' => 'value', 'label' => t('Current Value')],
            ['type' => 'number', 'name' => 'speed', 'label' => t('Animation Speed (ms)')],
        ];
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
