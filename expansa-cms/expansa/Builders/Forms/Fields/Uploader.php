<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * Renders `form/uploader.blade.php` - a drag-and-drop file uploader with a
 * configurable maximum size.
 */
class Uploader extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'uploader',
            label: t('File Uploader'),
            category: 'media',
            icon: 'ph ph-upload-simple',
            description: t('A drag-and-drop file uploader with a configurable maximum size.'),
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
            ['type' => 'text', 'name' => 'max_size', 'label' => t('Maximum file size')],
            ['type' => 'text', 'name' => 'attributes.accept', 'label' => t('Accepted file types')],
        ];
    }

    public function validate(array $field = []): array
    {
        $rule = isset($field['max_size']) ? 'maxSize:' . $field['max_size'] : '';

        return [($field['name'] ?? '') => $this->withRequired($field, $rule)];
    }
}
