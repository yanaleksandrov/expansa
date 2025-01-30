<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Expansa\Builders\Forms\Field;
use Expansa\Facades\Safe;
use InvalidArgumentException;

/**
 * Forms builder.
 *
 * @package Expansa
 */
final class Form
{
    public static array $forms = [];

    public static array $fields = [];

    public function configure(array $fields): void
    {
        self::$fields = $fields;
    }

    /**
     * Register new form.
     *
     * @param string $uid Unique Form ID.
     * @param array  $attributes
     * @param array  $fields
     * @return string
     */
    public function enqueue(string $uid, array $attributes = [], array $fields = []): string
    {
        $uid = Safe::id($uid);
        if (! $uid) {
            throw new InvalidArgumentException(t('The form with ":formUid" ID is empty.', $uid));
        }

        if (isset(self::$forms[$uid])) {
            throw new InvalidArgumentException(t('The form with ":formUid" ID is already exists.', $uid));
        }

        self::$forms[$uid] = new \Expansa\Builders\Forms\Form($uid, $fields, $attributes);

        return $uid;
    }

    /**
     * Register new form.
     *
     * @param string $uid Unique Form ID.
     * @return string
     */
    public function make(string $uid): string
    {
        $uid = Safe::id($uid);
        if (! $uid) {
            throw new InvalidArgumentException(t('The form with ":formUid" ID is empty.', $uid));
        }

        $form = self::$forms[$uid] ?? null;
        if ($form instanceof \Expansa\Builders\Forms\Form) {
            $html = $form->parse($form->fields ?? []);
            return $form->wrap($form->attributes, $html);
        }
        return '';
    }

    public function parse(array $fields): string
    {
        return (new Field())->parse($fields);
    }

    /**
     * Deregister form.
     *
     * @param string $uid Unique Form ID.
     * @return void
     */
    public function dequeue(string $uid): void
    {
        unset(self::$forms[$uid]);
    }

    /**
     * Get all data of form by name.
     *
     * @param string   $uid
     * @param callable $function
     * @return Form
     */
    public function override(string $uid, callable $function): Form
    {
        $form = self::$forms[$uid];
        if (is_callable($function)) {
            $function($form);
        }
        return $form;
    }
}
