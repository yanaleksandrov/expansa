<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * A generic single-file input. There is no dedicated `form/file.blade.php` template
 * (unlike the richer {@see Uploader}), so this reuses `form/input.blade.php` with
 * its `type` attribute forced to `file`.
 */
class File extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'file',
            label: t('File'),
            category: 'media',
            icon: 'ph ph-file',
            description: t('A single generic file upload field.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'attributes' => ['type' => 'file'],
            ],
        );
    }

    public function view(): string
    {
        return 'form/input';
    }

    public function render(array $field = []): string
    {
        $field['attributes'] = [...($field['attributes'] ?? []), 'type' => 'file'];

        return parent::render($field);
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
        $rule = isset($field['attributes']['accept'])
            ? 'extension:' . str_replace('.', '', (string) $field['attributes']['accept'])
            : '';

        return [($field['name'] ?? '') => $this->withRequired($field, $rule)];
    }
}
