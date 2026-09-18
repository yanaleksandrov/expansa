<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/image.blade.php` - a single image/avatar uploader with camera capture support.
 */
class Image extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'image',
            label: t('Image'),
            category: 'media',
            icon: 'ph ph-user-circle',
            description: t('A single image/avatar uploader with camera capture support.'),
            defaults: [
                'name'  => '',
                'label' => '',
            ],
        );
    }

    public function settings(): array
    {
        return $this->baseSettings(false);
    }

    public function validate(array $field = []): array
    {
        return [];
    }
}
