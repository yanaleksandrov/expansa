<?php

declare(strict_types=1);

namespace Dashboard;

use InvalidArgumentException;
use Expansa\{
    Patterns,
    Safe,
};

/**
 * Forms builder.
 *
 * @package Expansa
 */
class Form
{
    use Forms\Traits;
    use Forms\Traits\Form;
    use Patterns\Singleton;

    public static function configure(array $collection)
    {
    }

    public static function build(array $fields): string
    {
        return self::init()->parseFields($fields);
    }

    /**
     * Register new form.
     *
     * @param string $uid       Unique Form ID.
     * @param array $attributes Html attributes list.
     * @param array $fields     Fields list. Contains a nested array of arrays.
     * @return string
     */
    public static function enqueue(string $uid, array $attributes = [], array $fields = []): string
    {
        $uid = Safe::id($uid);
        if (! $uid) {
            throw new InvalidArgumentException(t('The form with :formUid ID is empty.', $uid));
        }

        $form = self::init($uid);
        if (isset($form->uid)) {
            throw new InvalidArgumentException(
                t('The form identified by %s already exists! Potential conflicts detected!', $uid)
            );
        }

        $form->uid        = $uid;
        $form->fields     = $fields;
        $form->attributes = [ 'id' => $uid, 'method' => 'POST', ...$attributes ];

        return $uid;
    }

    /**
     * Deregister form.
     * TODO: write this part
     *
     * @param string $uid Unique Form ID.
     * @return void
     *
     * @since 2025.1
     */
    public static function dequeue(string $uid)
    {
    }

    /**
     * Get all data of form by name.
     *
     * @param string $uid
     * @param callable|null $function
     * @return Form
     *
     * @since 2025.1
     */
    public static function override(string $uid, ?callable $function = null): Form
    {
        $form = self::init($uid);

        if (is_callable($function)) {
            call_user_func($function, $form);
        }

        return $form;
    }

    /**
     * Get form markup.
     *
     * @param string $path               Path to register form.
     * @param bool $withoutFormWrapper
     * @return string
     */
    public static function get(string $path, bool $withoutFormWrapper = false): string
    {
        $content = '';

        $uid = require_once $path;
        if (! empty($uid)) {
            $form    = self::init($uid);
            $content = trim($form->parseFields($form->fields ?? []));

            // return only the contents of the form without its wrapper
            if (! $withoutFormWrapper && $content) {
                return $form->wrap($form->attributes, $content);
            }
        } else {
            throw new InvalidArgumentException(
                t('From::enqueue located along the "%s" path should return the form ID.', $path)
            );
        }

        return $content;
    }

    /**
     * Output form markup.
     *
     * @param string $path               Path to register form.
     * @param bool $withoutFormWrapper
     * @return void
     *
     * @since 2025.1
     */
    public static function print(string $path, bool $withoutFormWrapper = false): void
    {
        echo self::get($path, $withoutFormWrapper);
    }

    /**
     * Adding a new field to any place of the fields list. TODO: Support nested arrays with using "dot" notation.
     *
     * @param array $field
     * @return void
     */
    public function insert(array $field): void
    {
        $name = Safe::name($field['name'] ?? '');
        if (empty($name)) {
            throw new InvalidArgumentException(t('It is not possible to add a field with an empty "name"'));
        }

        $this->insertField($this->fields, $field, $this);

        $this->after   = '';
        $this->before  = '';
        $this->instead = '';
    }

    /**
     * Bulk adding fields.
     *
     * @param array $fields
     * @return void
     *
     * @since 2025.1
     */
    public function attach(array $fields): void
    {
        foreach ($fields as $field) {
            $this->insertField($this->fields, $field, $this);
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
     *
     * @since 2025.1
     */
    public function attributes(array $attributes): void
    {
        $this->attributes = [ ...$this->attributes, ...$attributes ];
    }

    /**
     * Insert a new field after the specified one.
     *
     * @param string $field_name
     * @return Form
     *
     * @since 2025.1
     */
    public function after(string $field_name): Form
    {
        $this->after = $field_name;

        return $this;
    }

    /**
     * Insert a new field before the specified one.
     *
     * @param string $field_name
     * @return Form
     *
     * @since 2025.1
     */
    public function before(string $field_name): Form
    {
        $this->before = $field_name;

        return $this;
    }

    /**
     * Insert a new field instead the specified one.
     *
     * @param string $field_name
     * @return Form
     *
     * @since 2025.1
     */
    public function instead(string $field_name): Form
    {
        $this->instead = $field_name;

        return $this;
    }
}
