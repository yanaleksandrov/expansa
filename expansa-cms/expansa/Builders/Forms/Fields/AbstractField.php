<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms\Fields;

use Expansa\Builders\Forms\Contracts\Field;
use Expansa\Facades\Asset;
use Expansa\Facades\Safe;
use Expansa\Facades\View;

/**
 * Base class for all field type descriptors registered via {@see \Expansa\Facades\Form::configure()}.
 *
 * A subclass describes one entry in the field type palette (label/icon/description),
 * knows which `dashboard/views/form/*.blade.php` template renders it, and provides the
 * settings & validation rules for that field type.
 */
abstract class AbstractField implements Field
{
    public function __construct(

        /**
         * Template name under `views/form/` (without extension).
         */
        public readonly string $type,

        /**
         * Human-readable name shown in the field type picker.
         */
        public readonly string $label,

        /**
         * Group the field type is listed under (basic, choice, layout, media, ...).
         */
        public readonly string $category,

        /**
         * Phosphor icon class, e.g. `ph ph-text-t`.
         */
        public readonly string $icon,

        /**
         * Short explanation shown in the field type picker.
         */
        public readonly string $description,

        /**
         * Optional path/markup used to preview the field type.
         */
        public readonly string $preview = '',

        /**
         * Default field configuration merged under caller-provided values.
         */
        public readonly array $defaults = [],
    ) {} // phpcs:ignore

    /**
     * View template name that renders this field type.
     */
    public function view(): string
    {
        return "form/{$this->type}";
    }

    public function assets(): void
    {
        Asset::discover(View::make($this->view())->getPath(), $this->type, ['type' => $this->type]);
    }

    public function render(array $field = []): string
    {
        return (string) View::make($this->view(), [...$this->defaults, ...$field]);
    }

    /**
     * Settings shared by most field types: label, name and instructions,
     * plus an optional "required" toggle.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function baseSettings(bool $withRequired = true): array
    {
        return [
            [
                'type'       => 'text',
                'name'       => 'label',
                'label'      => t('Label'),
                'attributes' => ['required' => true],
            ],
            [
                'type'       => 'text',
                'name'       => 'name',
                'label'      => t('Name'),
                'attributes' => ['required' => true],
            ],
            [
                'type'  => 'text',
                'name'  => 'instruction',
                'label' => t('Instructions'),
            ],
            ...($withRequired ? [
                [
                    'type'    => 'checkbox',
                    'name'    => 'required',
                    'label'   => '',
                    'options' => [
                        'required' => [
                            'content' => t('Required')
                        ]
                    ],
                ],
            ] : []),
        ];
    }

    /**
     * Build a `required|<rule>` validation string for the field's `required` attribute.
     *
     * {@see \Expansa\Security\Validator} always runs every listed rule, even against an
     * empty value, so a format rule (email, numeric, ...) is only meaningful once the
     * field is actually required - an optional, empty field would otherwise fail it.
     *
     * @param array $field Field configuration to read the `required` attribute from.
     */
    protected function withRequired(array $field, string $rule = ''): string
    {
        if (! Safe::bool($field['attributes']['required'] ?? false)) {
            return '';
        }

        return trim(implode('|', array_filter(['required', $rule])), '|');
    }
}
