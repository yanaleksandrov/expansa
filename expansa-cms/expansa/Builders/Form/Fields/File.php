<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

/**
 * A generic single-file input. There is no dedicated `file` template
 * (unlike the richer {@see Uploader}), so this reuses the `input` template with
 * its `type` attribute forced to `file`.
 */
final class File extends AbstractField
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

    public function template(): string
    {
        return 'input';
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
            [
                'type'  => 'text',
                'name'  => 'attributes.accept',
                'label' => t('Accepted File Types'),
            ],
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
