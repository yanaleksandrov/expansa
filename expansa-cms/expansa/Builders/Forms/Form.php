<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms;

use Expansa\Support\Arr;

/**
 * A registered form: its fields and attributes, changed through Form::override(), rendered by Field::parse().
 *
 * @package Expansa\Builders\Forms
 */
final class Form extends Field
{
    /**
     * Name of the field the next attach() inserts after.
     */
    public private(set) string $after = '';

    /**
     * Name of the field the next attach() inserts before.
     */
    public private(set) string $before = '';

    /**
     * Name of the field the next attach() replaces.
     */
    public private(set) string $instead = '';

    public function __construct(

        /**
         * Unique ID of the form.
         */
        public readonly string $uid,

        /**
         * Fields of the form.
         */
        public array $fields = [],

        /**
         * Attributes of the form tag, `id` and `method="POST"` by default.
         */
        public array $attributes = [] {
            set => ['id' => $this->uid, 'method' => 'POST', ...$value];
        },
    ) {}

    /**
     * Wrap the rendered fields in a form tag.
     *
     * @param array  $attributes Attributes of the tag, usually $this->attributes.
     * @param string $content
     * @return string
     */
    public function wrap(array $attributes, string $content = ''): string
    {
        return sprintf("<form%s>\n%s</form>\n", Arr::toHtmlAtts($attributes), $content);
    }

    /**
     * Add fields at the position set by after(), before() or instead(), at the end by default.
     *
     * @param array $fields
     * @return void
     */
    public function attach(array $fields): void
    {
        foreach ($fields as $field) {
            $this->insert($field);
        }

        $this->after   = '';
        $this->before  = '';
        $this->instead = '';
    }

    /**
     * Merge attributes into the form attributes.
     *
     * @param array $attributes
     * @return void
     */
    public function attributes(array $attributes): void
    {
        $this->attributes = [...$this->attributes, ...$attributes];
    }

    /**
     * Insert the next attached fields after a field.
     *
     * @param string $fieldName
     * @return static
     */
    public function after(string $fieldName): static
    {
        $this->after = $fieldName;

        return $this;
    }

    /**
     * Insert the next attached fields before a field.
     *
     * @param string $fieldName
     * @return static
     */
    public function before(string $fieldName): static
    {
        $this->before = $fieldName;

        return $this;
    }

    /**
     * Replace a field with the next attached field.
     *
     * @param string $fieldName
     * @return static
     */
    public function instead(string $fieldName): static
    {
        $this->instead = $fieldName;

        return $this;
    }

    /**
     * Insert a field at the position set by after(), before() or instead(), at the end by default.
     *
     * @param array $field
     * @return void
     */
    public function insert(array $field): void
    {
        $location = current(array_filter([$this->after, $this->before, $this->instead]));
        $index    = $location ? array_search($location, array_column($this->fields, 'name'), true) : false;
        $fields   = $this->fields;

        match (true) {
            $index === false       => $fields[] = $field,
            $this->after !== ''    => array_splice($fields, $index + 1, 0, [$field]),
            $this->before !== ''   => array_splice($fields, $index, 0, [$field]),
            default                => $fields[$index] = $field,
        };

        $this->fields = $fields;
    }
}
