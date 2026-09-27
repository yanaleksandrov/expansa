<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Closure;
use InvalidArgumentException;
use Expansa\Builders\Forms\Field;
use Expansa\Builders\Forms\Form as FormInstance;
use Expansa\Security\Sanitizer;

/**
 * Registry of forms, the Form facade instance. Templates are rendered and their assets connected
 * by the `view` and `assets` callbacks from configure(), so the builder does not use the View and Asset facades.
 *
 * @package Expansa\Builders
 */
final class Form
{
    /**
     * Registered forms by uid.
     *
     * @var array<string, FormInstance>
     */
    private static array $forms = [];

    /**
     * Field type classes by type name.
     *
     * @var array<string, class-string<Forms\Contracts\Field>>
     */
    private static array $fields = [];

    /**
     * Renders a template: gets its name and data, returns HTML.
     */
    private static ?Closure $view = null;

    /**
     * Connects the CSS and JS of a template: gets its name and the asset uid.
     */
    private static ?Closure $assets = null;

    /**
     * Set the field types, the template renderer and the template assets loader.
     *
     * @param array<string, class-string<Forms\Contracts\Field>> $fields Field type classes by type name.
     * @param Closure|null                                       $view   `fn (string $template, array $data): string`.
     * @param Closure|null                                       $assets `fn (string $template, string $uid): void`.
     * @return void
     */
    public function configure(array $fields = [], ?Closure $view = null, ?Closure $assets = null): void
    {
        self::$fields = $fields;
        self::$view   = $view;
        self::$assets = $assets;
    }

    /**
     * Get the configured field type classes.
     *
     * @return array<string, class-string<Forms\Contracts\Field>>
     */
    public function getFields(): array
    {
        return self::$fields;
    }

    /**
     * Get the registered forms.
     *
     * @return array<string, FormInstance>
     */
    public function getForms(): array
    {
        return self::$forms;
    }

    /**
     * Render a template with the configured renderer, an empty string without configure().
     *
     * @param string $template Template name, e.g. `form/input`.
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
     * @param string $template Template name, e.g. `form/input`.
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
     * Register a form.
     *
     * @param string $uid Unique form id.
     * @param array  $attributes
     * @param array  $fields
     * @return string The sanitized uid.
     * @throws InvalidArgumentException If the uid is empty or taken.
     */
    public function enqueue(string $uid, array $attributes = [], array $fields = []): string
    {
        $uid = $this->uid($uid);
        if (isset(self::$forms[$uid])) {
            throw new InvalidArgumentException(sprintf('A form with ID "%s" already exists.', $uid));
        }

        self::$forms[$uid] = new FormInstance($uid, $fields, $attributes);

        return $uid;
    }

    /**
     * Render a registered form, an empty string for an unknown one.
     *
     * @param string $uid
     * @return string
     * @throws InvalidArgumentException If the uid is empty.
     */
    public function render(string $uid): string
    {
        $form = self::$forms[$this->uid($uid)] ?? null;
        if ($form === null) {
            return '';
        }

        return $form->wrap($form->attributes, $form->parse($form->fields));
    }

    /**
     * Render fields without a form tag.
     *
     * @param array $fields
     * @return string
     */
    public function parse(array $fields): string
    {
        return new Field()->parse($fields);
    }

    /**
     * Forget a registered form.
     *
     * @param string $uid
     * @return void
     */
    public function dequeue(string $uid): void
    {
        unset(self::$forms[$uid]);
    }

    /**
     * Change a registered form: add fields, attributes.
     *
     * @param string                       $uid
     * @param callable(FormInstance): void $function
     * @return FormInstance
     * @throws InvalidArgumentException If the form does not exist.
     */
    public function override(string $uid, callable $function): FormInstance
    {
        $form = self::$forms[$uid] ?? throw new InvalidArgumentException(sprintf('The form with ID "%s" does not exist.', $uid));

        $function($form);

        return $form;
    }

    /**
     * Sanitize a form uid.
     *
     * @param string $uid
     * @return string
     * @throws InvalidArgumentException If nothing is left.
     */
    private function uid(string $uid): string
    {
        return Sanitizer::id($uid) ?: throw new InvalidArgumentException(sprintf('The form ID "%s" is empty.', $uid));
    }
}
