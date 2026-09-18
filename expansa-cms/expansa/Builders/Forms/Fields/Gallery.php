<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Multiple image uploads with inline previews. There is no dedicated
 * `form/gallery.blade.php` template, so this reuses `form/media.blade.php`
 * (which already supports multiple items) with `multiple` and an image `accept` filter forced on.
 */
class Gallery extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'gallery',
            label: t('Gallery'),
            category: 'media',
            icon: 'ph ph-images',
            description: t('Multiple image uploads with inline previews.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'attributes' => ['multiple' => true, 'accept' => 'image/*'],
            ],
        );
    }

    public function view(): string
    {
        return 'form/media';
    }

    public function render(array $field = []): string
    {
        $field['attributes'] = [
            ...($field['attributes'] ?? []),
            'multiple' => true,
            'accept'   => $field['attributes']['accept'] ?? 'image/*',
        ];

        return parent::render($field);
    }

    public function settings(): array
    {
        return $this->baseSettings(false);
    }

    public function validate(array $field = []): array
    {
        return [($field['name'] ?? '') => $this->withRequired($field, 'array')];
    }
}
