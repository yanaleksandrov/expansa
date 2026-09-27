<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

use Expansa\Database\Attribute;
use Expansa\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Model attributes with mutators and change tracking.
 *
 * @package Expansa\Database\Traits
 */
trait HasAttributes
{
    /**
     * Raw attribute values by snake_case key, without mutators.
     *
     * @var array<string, mixed>
     */
    public array $attributes = [];

    /**
     * Attributes as of the last syncOriginals(), changes are compared with them.
     *
     * @var array<string, mixed>
     */
    protected array $originals = [];

    /**
     * Get an attribute through its read mutator.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        $key       = Str::snake($key);
        $attribute = $this->mutatorAttribute($key);

        if ($attribute?->get !== null) {
            return ($attribute->get)($this->attributes[$key] ?? null, $this->attributes);
        }

        return $this->attributes[$key] ?? null;
    }

    /**
     * Set an attribute through its write mutator.
     *
     * @param string $key
     * @param mixed  $value
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $key       = Str::snake($key);
        $attribute = $this->mutatorAttribute($key);

        $this->attributes[$key] = $attribute?->set !== null ? ($attribute->set)($value, $this->attributes) : $value;

        return $this;
    }

    /**
     * Mark the current attributes as saved.
     *
     * @return void
     */
    public function syncOriginals(): void
    {
        $this->originals = $this->attributes;
    }

    public function isChanged(): bool
    {
        return array_any(array_keys($this->attributes), fn ($key) => ! $this->originalIsEquivalent($key));
    }

    /**
     * Get the attributes changed since the last syncOriginals(), save() writes only them.
     *
     * @return array<string, mixed>
     */
    public function getChanges(): array
    {
        return array_filter($this->attributes, fn ($key) => ! $this->originalIsEquivalent($key), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get the given attributes through their read mutators.
     *
     * @param string[]|string $keys A list of keys or each key as an argument.
     * @return array<string, mixed>
     */
    public function only(array|string $keys): array
    {
        $result = [];
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $result[$key] = $this->getAttribute($key);
        }

        return $result;
    }

    /**
     * Whether a camelCase method returns an Attribute, cached per class: reflection is the expensive part.
     *
     * @param string $method
     * @return bool
     */
    protected function hasMutator(string $method): bool
    {
        static $cache = [];

        if (isset($cache[static::class][$method])) {
            return $cache[static::class][$method];
        }

        if (! method_exists($this, $method)) {
            return $cache[static::class][$method] = false;
        }

        $type = new ReflectionMethod($this, $method)->getReturnType();

        return $cache[static::class][$method] = $type instanceof ReflectionNamedType && $type->getName() === Attribute::class;
    }

    /**
     * Get the mutators of a snake_case attribute, calling its method once per access.
     *
     * @param string $key
     * @return Attribute|null
     */
    protected function mutatorAttribute(string $key): ?Attribute
    {
        $method = Str::camel($key);

        return $this->hasMutator($method) ? $this->$method() : null;
    }

    protected function originalIsEquivalent(string $key): bool
    {
        return array_key_exists($key, $this->originals) && ($this->attributes[$key] ?? null) === $this->originals[$key];
    }
}
