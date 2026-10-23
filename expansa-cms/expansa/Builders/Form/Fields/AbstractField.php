<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Fields;

use Expansa\Builders\Form;
use Expansa\Builders\Form\Contracts\Field;
use Expansa\Security\Sanitizer;

/**
 * Base class for all field type descriptors registered via {@see \Expansa\Builders\Form::configure()}.
 *
 * A subclass describes one entry in the field type palette (label/icon/description),
 * names the template that renders it, and provides the settings & validation rules for that field type.
 */
abstract class AbstractField implements Field
{
    public function __construct(

        /**
         * Type name, also the name of the template that renders it unless template() says otherwise.
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
    ) {}

    /**
     * Name of the template that renders this field type; the `view` callback of Form::configure() finds its file.
     *
     * @return string
     */
    public function template(): string
    {
        return $this->type;
    }

    public function assets(): void
    {
        Form::assets($this->template(), $this->type);
    }

    public function render(array $field = []): string
    {
        return Form::view($this->template(), [...$this->defaults, ...$field]);
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
                'error'      => 'label',
                'label'      => t('Label'),
                'attributes' => ['required' => true],
            ],
            [
                'type'       => 'text',
                'name'       => 'name',
                'error'      => 'name',
                'label'      => t('Name', context: 'field'),
                'attributes' => ['required' => true],
            ],
            [
                'type'  => 'text',
                'name'  => 'instruction',
                'error' => 'instruction',
                'label' => t('Instructions'),
            ],
            ...($withRequired ? [
                [
                    'type'    => 'checkbox',
                    'name'    => 'required',
                    'error'   => 'required',
                    'label'   => '',
                    'options' => [
                        'required' => [
                            'content' => t('Required'),
                        ],
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
        if (! Sanitizer::bool($field['attributes']['required'] ?? false)) {
            return '';
        }

        return trim(implode('|', array_filter(['required', $rule])), '|');
    }

    /**
     * Rules of a text with `minlength` and `maxlength`: as in the browser, an empty optional value
     * passes the minimum, only the maximum checks it.
     *
     * @param array<string, mixed> $field
     * @param string               $rule  Rule of the value besides the length, e.g. `email`.
     * @return string
     */
    protected function withLength(array $field, string $rule = ''): string
    {
        $min = Sanitizer::absint($field['attributes']['minlength'] ?? 0);
        $max = Sanitizer::absint($field['attributes']['maxlength'] ?? 0);

        $rules = $this->withRequired($field, implode('|', array_filter([
            $rule,
            $min > 0 ? 'lengthMin:' . $min : '',
            $max > 0 ? 'lengthMax:' . $max : '',
        ])));

        return $rules !== '' || $max === 0 ? $rules : 'lengthMax:' . $max;
    }

    /**
     * Settings of the length limits of a text, `minlength` and `maxlength` of its input.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function lengthSettings(): array
    {
        return [
            [
                'type'       => 'number',
                'name'       => 'attributes.minlength',
                'error'      => 'attributes.minlength',
                'label'      => t('Minimum Length'),
                'attributes' => ['min' => 0],
            ],
            [
                'type'       => 'number',
                'name'       => 'attributes.maxlength',
                'error'      => 'attributes.maxlength',
                'label'      => t('Maximum Length'),
                'attributes' => ['min' => 0],
            ],
        ];
    }
}
