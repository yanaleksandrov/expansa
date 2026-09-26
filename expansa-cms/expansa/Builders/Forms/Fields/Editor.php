<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

/**
 * A rich text editor for formatted content. No WYSIWYG asset is wired into the
 * dashboard yet, so this renders `form/textarea.blade.php` as a plain-text fallback
 * until a real editor (and its own template) is registered.
 */
class Editor extends AbstractField
{
    public function __construct()
    {
        parent::__construct(
            type: 'editor',
            label: t('Rich Text Editor'),
            category: 'advanced',
            icon: 'ph ph-text-aa',
            description: t('A rich text editor for formatted content.'),
            defaults: [
                'name'       => '',
                'label'      => '',
                'attributes' => ['rows' => 8],
            ],
        );
    }

    public function view(): string
    {
        return 'form/textarea';
    }

    public function settings(): array
    {
        return $this->baseSettings();
    }

    public function validate(array $field = []): array
    {
        return [($field['name'] ?? '') => $this->withRequired($field)];
    }
}
