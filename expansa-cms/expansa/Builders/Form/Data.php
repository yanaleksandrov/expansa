<?php

declare(strict_types=1);

namespace Expansa\Builders\Form;

use Expansa\Security\Sanitizer;
use Expansa\Support\Arr;

/**
 * Data of a field template, `$field` in it: Form::view() fills it from the field array, each property
 * cleans its value in a set hook. A key means the same in every template, so one class serves them all;
 * default classes differ by template and stay in its markup (`$field->class ?: 'field'`).
 * Keys without a property (`type`, `error`) are dropped; `prop`, `required`
 * and `htmlAttributes` are computed.
 *
 * @package Expansa\Builders
 */
final class Data
{
    /**
     * Properties computed from the others, a field key cannot set them.
     */
    private const array COMPUTED = [
        'prop'           => true,
        'required'       => true,
        'htmlAttributes' => true,
    ];

    /**
     * Name attribute of the field.
     */
    public string $name = '' {
        set(mixed $value) => Sanitizer::name($value);
    }

    /**
     * Label, may contain markup.
     */
    public string $label = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Classes of the field wrapper, the template has its own default.
     */
    public string $class = '' {
        set(mixed $value) => Sanitizer::class($value);
    }

    /**
     * Classes of the label.
     */
    public string $label_class = '' {
        set(mixed $value) => Sanitizer::class($value);
    }

    /**
     * Layout of a group: `row` puts the label in the left fifth, `compact` in a muted left third,
     * `stack` above the fields, `plain` is a container without the label column.
     */
    public string $layout = 'row' {
        set(mixed $value) => in_array($value, ['row', 'compact', 'stack', 'plain'], true) ? $value : 'row';
    }

    /**
     * Number of columns of the fields in a group, 1 to 4.
     */
    public int $columns = 1 {
        set(mixed $value) => min(max(Sanitizer::absint($value), 1), 4);
    }

    /**
     * Whether the field has a reset button.
     */
    public bool $reset = false {
        set(mixed $value) => Sanitizer::bool($value);
    }

    /**
     * Markup before the input.
     */
    public string $before = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Markup after the input.
     */
    public string $after = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Hint under the field, may contain markup.
     */
    public string $instruction = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Error text under the field, may contain markup; the field gets `is-invalid`.
     * Not `error`: that key names the field in the errors of an API response.
     */
    public string $invalid = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Description of a media field.
     */
    public string $description = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Rendered nested fields of a step or group.
     */
    public string $content = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Tooltip text.
     */
    public string $tooltip = '' {
        set(mixed $value) => Sanitizer::attribute($value);
    }

    /**
     * Whether the field has a copy button.
     */
    public bool $copy = false {
        set(mixed $value) => Sanitizer::bool($value);
    }

    /**
     * Visibility attributes from Renderer: `u-show` and `hidden`.
     */
    public array $conditions = [] {
        set(mixed $value) => Sanitizer::array($value);
    }

    /**
     * HTML attributes of the input.
     */
    public array $attributes = [] {
        set(mixed $value) => Sanitizer::array($value);
    }

    /**
     * Options of a select, checkboxes or radios.
     */
    public array $options = [] {
        set(mixed $value) => Sanitizer::array($value);
    }

    /**
     * Tabs of a level, rendered together: the menu buttons and the panels with rendered nested fields.
     * `active` marks the tab shown when the page opens, `condition` and `visible` come from its conditions.
     *
     * @var array<int, array{prop: string, label: string, icon: string, caption: string, description: string,
     *     content: string, condition: string, visible: bool, active: bool}>
     */
    public array $tabs = [] {
        set(mixed $value) => array_map(
            fn (array $tab) => [
                'prop'        => Sanitizer::prop($tab['name'] ?? '') ?: 'tab',
                'label'       => Sanitizer::trim($tab['label'] ?? ''),
                'icon'        => Sanitizer::attribute($tab['icon'] ?? ''),
                'caption'     => Sanitizer::ucfirst($tab['caption'] ?? ''),
                'description' => Sanitizer::trim($tab['description'] ?? ''),
                'content'     => Sanitizer::trim($tab['content'] ?? ''),
                'condition'   => (string) ($tab['conditions']['u-show'] ?? ''),
                'visible'     => empty($tab['conditions']['hidden']),
                'active'      => ! empty($tab['active']),
            ],
            array_values(Sanitizer::array($value)),
        );
    }

    /**
     * Locations of the rule builder: key => {label, options}.
     */
    public array $locations = [] {
        set(mixed $value) => Sanitizer::array($value);
    }

    /**
     * Number of a form step.
     */
    public int $step = 1 {
        set(mixed $value) => ($value ?? '') === '' ? 1 : Sanitizer::absint($value);
    }

    /**
     * Whether a password field shows the visibility switcher.
     */
    public bool $switcher = true {
        set(mixed $value) => ($value ?? '') === '' || Sanitizer::bool($value);
    }

    /**
     * Whether a password field shows the strength indicator.
     */
    public bool $indicator = false {
        set(mixed $value) => Sanitizer::bool($value);
    }

    /**
     * Whether a password field has the generator.
     */
    public bool $generator = false {
        set(mixed $value) => Sanitizer::bool($value);
    }

    /**
     * Character sets of the password generator.
     */
    public array $characters = [] {
        set(mixed $value) => Sanitizer::array($value);
    }

    /**
     * Maximum of a progress bar.
     */
    public int $max = 0 {
        set(mixed $value) => Sanitizer::absint($value);
    }

    /**
     * Minimum of a progress bar.
     */
    public int $min = 0 {
        set(mixed $value) => Sanitizer::absint($value);
    }

    /**
     * Value of a progress bar.
     */
    public int $value = 100 {
        set(mixed $value) => ($value ?? '') === '' ? 100 : Sanitizer::absint($value);
    }

    /**
     * Animation speed of a progress bar, ms.
     */
    public int $speed = 1000 {
        set(mixed $value) => ($value ?? '') === '' ? 1000 : Sanitizer::absint($value);
    }

    /**
     * Upload limit shown by the uploader, `upload_max_filesize` when empty.
     */
    public string $max_size = '' {
        set(mixed $value) => Sanitizer::trim($value);
    }

    /**
     * Markup of a custom field: a callback that prints it or a string, not cleaned.
     *
     * @var callable|string|null
     */
    public mixed $callback = null;

    /**
     * Reactive property of the field for youla.js: `u-prop`, `$copy()`.
     */
    public string $prop {
        get => Sanitizer::prop($this->attributes['name'] ?? $this->name);
    }

    /**
     * Whether the input is required, the label gets `data-required`.
     */
    public bool $required {
        get => ! empty($this->attributes['required']);
    }

    /**
     * HTML attributes of the input as a string, with a leading space.
     */
    public string $htmlAttributes {
        get => Arr::toHtmlAttributes($this->attributes);
    }

    /**
     * Fill the data from a field array, keys without a property are dropped.
     *
     * @param array<string, mixed> $field
     */
    public function __construct(array $field = [])
    {
        foreach ($field as $key => $value) {
            if (! isset(self::COMPUTED[$key]) && property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
