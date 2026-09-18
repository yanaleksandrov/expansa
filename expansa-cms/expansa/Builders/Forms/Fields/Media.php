<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

use Expansa\Facades\Safe;

/**
 * Renders `form/media.blade.php` - attaches one or more media files with inline previews.
 */
class Media extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'media',
            label: t('Media'),
            category: 'media',
            icon: 'ph ph-image-square',
            description: t('Attach one or more media files with inline previews.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'attributes' => [],
            ],
        );
    }

    public function settings(): array
    {
        return [
            ...$this->baseSettings(false),
            ['type' => 'text', 'name' => 'attributes.accept', 'label' => t('Accepted file types')],
        ];
    }

    public function validate(array $field = []): array
    {
        $multiple = Safe::bool($field['attributes']['multiple'] ?? false);

        return [($field['name'] ?? '') => $this->withRequired($field, $multiple ? 'array' : '')];
    }
}
