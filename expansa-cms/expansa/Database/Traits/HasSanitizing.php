<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

use LogicException;

/**
 * Sanitizer rules applied by Model::setAttribute() to every attribute set through fill() or __set(),
 * with the sanitizer from Model::configure().
 *
 * @package Expansa\Database\Traits
 */
trait HasSanitizing
{
    /**
     * Get the rules by attribute: `['email' => 'trim|email', 'slug' => 'slug:$login']`.
     *
     * @return array<string, string>
     */
    abstract protected function getSanitizerRules(): array;

    /**
     * Get the rules, cached per class: setAttribute() asks for them once per attribute.
     *
     * @return array<string, string>
     */
    protected function sanitizerRules(): array
    {
        static $cache = [];

        return $cache[static::class] ??= $this->getSanitizerRules();
    }

    /**
     * Sanitize the current attributes again, such as after hydrate(). Mutators don't run:
     * a mutated value (a password hash) is already final.
     *
     * @return static
     * @throws LogicException If no sanitizer is configured.
     */
    final protected function sanitize(): static
    {
        $sanitizer = self::$sanitizer ?? throw new LogicException('Sanitizing rules of ' . static::class . ' need Model::configure(sanitizer: ...).');

        // the sanitizer returns only the keys with rules
        $this->attributes = $sanitizer($this->attributes, $this->sanitizerRules()) + $this->attributes;

        return $this;
    }
}
