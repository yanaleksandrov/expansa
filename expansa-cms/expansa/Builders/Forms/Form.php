<?php

declare(strict_types=1);

namespace Expansa\Builders\Forms;

use Expansa\Support\Arr;

class Form extends Field
{
    /**
     * Constructor for the Form class.
     *
     * @param string $uid Unique ID of the form class instance.
     * @param array $fields List of all form fields.
     * @param array $attributes Default attributes for the form.
     * @param string $before ID of the field before which the new field will be added.
     * @param string $after ID of the field after which the new field will be added.
     * @param string $instead ID of the field to be replaced with the new field.
     */
    public function __construct(
        public string $uid,
        public array $fields = [],
        public array $attributes = [],
        public string $before = '',
        public string $after = '',
        public string $instead = ''
    )
    {
        $this->attributes = ['id' => $uid, 'method' => 'POST', ...$attributes];
    }

    /**
     * Add 'form' tag wrapper for form content.
     *
     * @param array $attributes
     * @param string $content
     * @return string
     */
    public function wrap(array $attributes, string $content = ''): string
    {
        return sprintf("<form%s>\n%s</form>\n", Arr::toHtmlAtts($attributes), $content);
    }

    /**
     * Insert new fields to any place in existing form.
     *
     * @param array $fields
     * @param array $field
     * @param Form  $form
     */
    public function insert(array &$fields, array $field, Form $form): void
    {
        $index    = false;
        $location = current(array_filter([ $form->after, $form->before, $form->instead ]));
        if ($location) {
            $index = array_search($location, array_column($fields, 'name'), true);
        }

        if ($index !== false) {
            match (true) {
                !!$form->after   => array_splice($fields, $index + 1, 0, [ $field ]),
                !!$form->before  => array_splice($fields, $index, 0, [ $field ]),
                !!$form->instead => $fields[ $index ] = $field,
            };
        } else {
            $fields[] = $field;
        }
    }

    /**
     * Bulk adding fields.
     *
     * @param array $fields
     * @return void
     */
    public function attach(array $fields): void
    {
        foreach ($fields as $field) {
            $this->insert($this->fields, $field, $this);
        }

        $this->after   = '';
        $this->before  = '';
        $this->instead = '';
    }

    /**
     * Override form attributes.
     *
     * @param array $attributes
     * @return void
     */
    public function attributes(array $attributes): void
    {
        $this->attributes = [ ...$this->attributes, ...$attributes ];
    }

    /**
     * Insert a new field after the specified one.
     *
     * @param string $fieldName
     * @return Form
     */
    public function after(string $fieldName): self
    {
        $this->after = $fieldName;

        return $this;
    }

    /**
     * Insert a new field before the specified one.
     *
     * @param string $fieldName
     * @return Form
     */
    public function before(string $fieldName): self
    {
        $this->before = $fieldName;

        return $this;
    }

    /**
     * Insert a new field instead the specified one.
     *
     * @param string $fieldName
     * @return Form
     */
    public function instead(string $fieldName): self
    {
        $this->instead = $fieldName;

        return $this;
    }
}
