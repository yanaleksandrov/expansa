<?php

declare(strict_types=1);

namespace app\Models;

use Expansa\Support\Str;

class Model
{
    protected static string $repository;

    public function __construct(
        protected array $attributes = [],
        protected array $changedAttributes = [],
        protected ?string $updatedAt = null,
        protected ?string $deletedAt = null,
    ) {} // phpcs:ignore

    public function hasChanges(): bool
    {
        return count($this->changedAttributes) > 0;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getChangedAttributes(): array
    {
        return $this->changedAttributes;
    }

    public function resetChanges(): void
    {
        foreach ($this->changedAttributes as $name => $value) {
            $this->attributes[$name] = $value;
        }
        $this->changedAttributes = [];
    }

    public function save(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');

        static::store($this);

        $this->resetChanges();
    }

    public function remove(): void
    {
        $this->deletedAt = date("Y-m-d H:i:s");

        static::store($this);

        $this->resetChanges();
    }

    public function __get(string $name)
    {
        $name = Str::snake($name);

        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value)
    {
        $name = Str::snake($name);

        $this->changedAttributes[$name] = $value;

        if (! array_key_exists($name, $this->attributes)) {
            $this->attributes[$name] = $value;
        }

        if ($value === $this->attributes[$name]) {
            unset($this->changedAttributes[$name]);
        }
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if (method_exists(static::$repository, $name)) {
            return static::$repository::{$name}(...$arguments);
        }

        throw new \LogicException("Call to undefined method " . __CLASS__ . "::{$name}()");
    }
}
