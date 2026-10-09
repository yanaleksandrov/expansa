<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Closure;
use Expansa\Builders\Form\Internal\Renderer;
use Expansa\Security\Sanitizer;
use Expansa\Support\Arr;
use InvalidArgumentException;

/**
 * Form of the dashboard: its fields and the attributes of the form tag. Forms are registered by uid,
 * a plugin changes a registered one in override():
 *
 * ```php
 * Form::enqueue('profile', ['class' => 'dg g-7'], [
 *     ['type' => 'email', 'name' => 'email', 'label' => t('Email')],
 * ]);
 *
 * Form::override('profile', fn (Form $form) => $form->after('email', [
 *     ['type' => 'tel', 'name' => 'phone', 'label' => t('Phone')],
 * ]));
 *
 * echo Form::render('profile');
 * ```
 *
 * Templates are named by field type (`input`, `layout-tab`): the `view` and `assets` callbacks
 * of configure() find their files, so the builder knows neither the template directory nor the View
 * and Asset facades.
 *
 * @package Expansa\Builders
 */
final class Form
{
    /**
     * Registered forms by uid.
     *
     * @var array<string, Form>
     */
    private static array $forms = [];

    /**
     * Field type classes by type name.
     *
     * @var array<string, class-string<Form\Contracts\Field>>
     */
    private static array $types = [];

    /**
     * Renders a template: gets its name (the field type) and data, returns HTML.
     */
    private static ?Closure $view = null;

    /**
     * Connects the CSS and JS of a template: gets its name (the field type) and the asset uid.
     */
    private static ?Closure $assets = null;

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
     * Set the field types, the template renderer and the template assets loader.
     *
     * @param array<string, class-string<Form\Contracts\Field>> $types  Field type classes by type name.
     * @param Closure|null                                      $view   `fn (string $template, array $data): string`.
     * @param Closure|null                                      $assets `fn (string $template, string $uid): void`.
     * @return void
     */
    public static function configure(array $types = [], ?Closure $view = null, ?Closure $assets = null): void
    {
        self::$types  = $types;
        self::$view   = $view;
        self::$assets = $assets;
    }

    /**
     * Get the configured field type classes.
     *
     * @return array<string, class-string<Form\Contracts\Field>>
     */
    public static function getTypes(): array
    {
        return self::$types;
    }

    /**
     * Get the registered forms.
     *
     * @return array<string, Form>
     */
    public static function getForms(): array
    {
        return self::$forms;
    }

    /**
     * Register a form.
     *
     * @param string $uid        Unique form ID.
     * @param array  $attributes Attributes of the form tag.
     * @param array  $fields
     * @return string The sanitized uid.
     * @throws InvalidArgumentException If the uid is empty or taken.
     */
    public static function enqueue(string $uid, array $attributes = [], array $fields = []): string
    {
        $uid = self::uid($uid);
        if (isset(self::$forms[$uid])) {
            throw new InvalidArgumentException(sprintf('A form with ID "%s" already exists.', $uid));
        }

        self::$forms[$uid] = new Form($uid, $fields, $attributes);

        return $uid;
    }

    /**
     * Change a registered form, e.g. add fields: `fn (Form $form) => $form->after('email', [...])`.
     *
     * @param string                $uid
     * @param callable(Form): mixed $function
     * @return Form
     * @throws InvalidArgumentException If the uid is empty or the form does not exist.
     */
    public static function override(string $uid, callable $function): Form
    {
        $form = self::$forms[self::uid($uid)]
            ?? throw new InvalidArgumentException(sprintf('The form with ID "%s" does not exist.', $uid));

        $function($form);

        return $form;
    }

    /**
     * Forget a registered form.
     *
     * @param string $uid
     * @return void
     * @throws InvalidArgumentException If the uid is empty.
     */
    public static function forget(string $uid): void
    {
        unset(self::$forms[self::uid($uid)]);
    }

    /**
     * Render a registered form in a form tag, an empty string for an unknown one.
     *
     * @param string $uid
     * @return string
     * @throws InvalidArgumentException If the uid is empty.
     */
    public static function render(string $uid): string
    {
        $form = self::$forms[self::uid($uid)] ?? null;
        if ($form === null) {
            return '';
        }

        $fields = new Renderer()->render($form->fields);

        return sprintf("<form%s>\n%s</form>\n", Arr::toHtmlAttributes($form->attributes), $fields);
    }

    /**
     * Render fields without a form tag, e.g. a group of custom fields inside another form.
     *
     * @param array $fields
     * @return string
     */
    public static function renderFields(array $fields): string
    {
        return new Renderer()->render($fields);
    }

    /**
     * Render a template with the configured renderer, an empty string without configure().
     *
     * @param string $template Template name, e.g. `input`.
     * @param array  $data
     * @return string
     */
    public static function view(string $template, array $data = []): string
    {
        return self::$view !== null ? (self::$view)($template, $data) : '';
    }

    /**
     * Connect the CSS and JS of a template with the configured loader, nothing without configure().
     *
     * @param string $template Template name, e.g. `input`.
     * @param string $uid      Asset uid, templates shared by several field types get the type.
     * @return void
     */
    public static function assets(string $template, string $uid): void
    {
        if (self::$assets !== null) {
            (self::$assets)($template, $uid);
        }
    }

    /**
     * Insert fields before a field, at the end if there is no such field.
     *
     * @param string $target Name of the field.
     * @param array  $fields
     * @return static
     */
    public function before(string $target, array $fields): static
    {
        $index = $this->find($target);

        return $this->splice($index ?? count($this->fields), 0, $fields);
    }

    /**
     * Insert fields after a field, at the end if there is no such field.
     *
     * @param string $target Name of the field.
     * @param array  $fields
     * @return static
     */
    public function after(string $target, array $fields): static
    {
        $index = $this->find($target);

        return $this->splice($index === null ? count($this->fields) : $index + 1, 0, $fields);
    }

    /**
     * Replace a field with fields, add them at the end if there is no such field.
     *
     * @param string $target Name of the field.
     * @param array  $fields
     * @return static
     */
    public function replace(string $target, array $fields): static
    {
        $index = $this->find($target);

        return $index === null ? $this->append($fields) : $this->splice($index, 1, $fields);
    }

    /**
     * Insert fields at the start of the form.
     *
     * @param array $fields
     * @return static
     */
    public function prepend(array $fields): static
    {
        return $this->splice(0, 0, $fields);
    }

    /**
     * Add fields at the end of the form.
     *
     * @param array $fields
     * @return static
     */
    public function append(array $fields): static
    {
        return $this->splice(count($this->fields), 0, $fields);
    }

    /**
     * Remove a field, nothing if there is no such field.
     *
     * @param string $target Name of the field.
     * @return static
     */
    public function remove(string $target): static
    {
        $index = $this->find($target);

        return $index === null ? $this : $this->splice($index, 1, []);
    }

    /**
     * Merge attributes into the attributes of the form tag.
     *
     * @param array $attributes
     * @return static
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = [...$this->attributes, ...$attributes];

        return $this;
    }

    /**
     * Get the position of a field by name; fields without a name are counted too.
     *
     * @param string $name
     * @return int|null
     */
    private function find(string $name): ?int
    {
        return array_find_key(array_values($this->fields), fn (array $field) => ($field['name'] ?? '') === $name);
    }

    /**
     * Replace `$length` fields from the position with new ones.
     *
     * @param int   $offset
     * @param int   $length
     * @param array $fields
     * @return static
     */
    private function splice(int $offset, int $length, array $fields): static
    {
        $list = array_values($this->fields);
        array_splice($list, $offset, $length, $fields);
        $this->fields = $list;

        return $this;
    }

    /**
     * Sanitize a form uid.
     *
     * @param string $uid
     * @return string
     * @throws InvalidArgumentException If nothing is left.
     */
    private static function uid(string $uid): string
    {
        return Sanitizer::id($uid) ?: throw new InvalidArgumentException(sprintf('The form ID "%s" is empty.', $uid));
    }
}
