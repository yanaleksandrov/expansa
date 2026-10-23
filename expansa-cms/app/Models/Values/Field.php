<?php

declare(strict_types=1);

namespace App\Models\Values;

use Closure;
use DateTimeImmutable;

/**
 * Field of a menu item, see Menu::itemFields(): the editor shows it in the settings of the item,
 * Menu stores and sanitizes its value, a theme reads it from the item of the tree by its key.
 *
 * @package App\Models
 */
final readonly class Field
{
    public function __construct(

        /**
         * Name of the field in the editor.
         */
        public string $label,

        /**
         * Control of the field: `text`, `textarea`, `select`, `multiselect` or `date`, a `Y-m-d` date.
         */
        public string $type = 'text',

        /**
         * Classes of the icon before the name.
         */
        public string $icon = '',

        /**
         * Labels of the choices of a select by value.
         *
         * @var array<string, string>
         */
        public array $options = [],

        /**
         * Value of a new item and of an item the field is not for; an array for `multiselect`.
         */
        public string|array $default = '',

        /**
         * Text of the empty field.
         */
        public string $placeholder = '',

        /**
         * Types of the items the field is for, see Menu::TYPES; empty for every one.
         *
         * @var string[]
         */
        public array $types = [],

        /**
         * Sanitizes the value: `fn (mixed $value): mixed`; without it a text is trimmed and the choices
         * of a select are limited to its options.
         */
        public ?Closure $sanitizer = null,
    ) {}

    /**
     * Get the value to store from what the editor sent.
     *
     * @param mixed $value
     * @return string|array
     */
    public function sanitize(mixed $value): string|array
    {
        if ($this->sanitizer !== null) {
            return ($this->sanitizer)($value ?? $this->default);
        }

        $options = array_map(strval(...), array_keys($this->options));
        $chosen  = array_map(strval(...), array_filter((array) $value, is_scalar(...)));

        return match ($this->type) {
            'select'      => is_scalar($value) && in_array((string) $value, $options, true) ? (string) $value : $this->default,
            'multiselect' => array_values(array_intersect($options, $chosen)),
            'date'        => is_string($value) && self::isDate($value) ? $value : '',
            default       => is_scalar($value) ? trim((string) $value) : $this->default,
        };
    }

    /**
     * Whether the value is a day of the calendar as `Y-m-d`.
     *
     * @param string $value
     * @return bool
     */
    private static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
