<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

use Expansa\Support\Str;

trait HasAttributes
{
    /**
     * Per-(class, method) cache backing {@see self::hasMutator()}.
     *
     * @var array<class-string, array<string, bool>>
     */
    protected static array $attributeMutatorCache = [];

    /**
     * Array of model attributes.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Snapshot of $attributes as of the last {@see self::syncOriginals()} call - what
     * {@see self::getChanges()}/{@see self::isChanged()} diff the current values against.
     *
     * @var array<string, mixed>
     */
    protected array $originals = [];

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Replaces the whole attribute array outright - bypasses sanitizing/mutators entirely,
     * unlike {@see self::setAttribute()}. See {@see \Expansa\Database\Model::make()}.
     *
     * @param array<string, mixed> $attributes
     * @return static
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        $key       = Str::snake($key);
        $attribute = $this->mutatorAttribute($key);

        if ($attribute?->get !== null) {
            return call_user_func($attribute->get, $this->attributes[$key] ?? null, $this->attributes);
        }

        return $this->attributes[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $key       = Str::snake($key);
        $attribute = $this->mutatorAttribute($key);

        if ($attribute?->set !== null) {
            $this->attributes[$key] = call_user_func($attribute->set, $value, $this->attributes);
        } else {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    /**
     * Marks the current attribute values as the "clean" baseline - called after save() and
     * after hydrating from the database (see {@see \Expansa\Database\Model::make()}).
     *
     * @return void
     */
    public function syncOriginals(): void
    {
        $this->originals = $this->attributes;
    }

    /**
     * Whether any attribute differs from {@see self::$originals}.
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        foreach ($this->attributes as $key => $val) {
            if (! $this->originalIsEquivalent($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The attributes that differ from {@see self::$originals} - what {@see \Expansa\Database\Query::save()}
     * actually writes on an update.
     *
     * @return array<string, mixed>
     */
    public function getChanges(): array
    {
        return array_filter($this->attributes, function ($key) {
            return ! $this->originalIsEquivalent($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get just the given attributes (through {@see self::getAttribute()}, so mutators still apply).
     *
     * @param mixed $keys An array of keys, or each key as its own argument.
     * @return array<string, mixed>
     */
    public function only(mixed $keys): array
    {
        $result = [];

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            $result[$key] = $this->getAttribute($key);
        }

        return $result;
    }

    /**
     * Whether $method (already camelCase) is a mutator method - cached per (class, method), since
     * reflecting on the return type is the expensive part and a class's methods never change at
     * runtime. Takes the method name directly (not the snake_case attribute key) so callers that
     * already computed it via Str::camel() don't pay for a second, redundant conversion.
     *
     * @param string $method
     * @return bool
     */
    protected function hasMutator(string $method): bool
    {
        if (isset(static::$attributeMutatorCache[static::class][$method])) {
            return static::$attributeMutatorCache[static::class][$method];
        }

        if (! method_exists($this, $method)) {
            return static::$attributeMutatorCache[static::class][$method] = false;
        }

        $returnType = new \ReflectionMethod($this, $method)->getReturnType();

        return static::$attributeMutatorCache[static::class][$method] =
            $returnType instanceof \ReflectionNamedType &&
            $returnType->getName() === Attribute::class;
    }

    /**
     * Resolves $key's Attribute mutator object (see {@see Model\Attribute}), or null if $key has
     * no mutator method at all - invokes the mutator method (e.g. `password()`) at most once per
     * getAttribute()/setAttribute() call. The previous shape (hasGetMutator()+getMutatorValue(),
     * or hasSetMutator()+setMutatorValue()) each called the mutator method twice per access - once
     * to check whether ->get/->set was non-null, again to actually use it - doubling the
     * Attribute-object-and-closures allocation cost of every mutated attribute read or write.
     *
     * @param string $key snake_case attribute name.
     * @return ?Attribute
     */
    protected function mutatorAttribute(string $key): ?Attribute
    {
        $method = Str::camel($key);

        return $this->hasMutator($method) ? $this->$method() : null;
    }

    /**
     * Whether $key's current value is the same as when {@see self::syncOriginals()} last ran.
     *
     * @param string $key
     * @return bool
     */
    protected function originalIsEquivalent(string $key): bool
    {
        return array_key_exists($key, $this->originals)
            && ($this->attributes[$key] ?? null) === $this->originals[$key];
    }
}
