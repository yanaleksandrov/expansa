<?php

declare(strict_types=1);

namespace Expansa\Database;

use Exception;
use Expansa\Facades\Safe;
use Expansa\Database\Model\HasReadonlyAttributes;
use Expansa\Database\Model\HasSanitizing;
use Expansa\Support\Str;
use stdClass;

/**
 * Base data model class with support for attributes, mass assignment protection, timestamps, and soft deletes.
 *
 * @method static static|null get(mixed $value, string $by = 'id') Find a model by primary key or specified field.
 * @method static static      fill(array $data)                    Create a model by...
 * @method static bool        exists(array $data)                  Check record is existing.
 * @method int                save()                               Delete records by primary key.
 * @method int                delete()                             Delete records by primary key.
 * @method int                restore()                            Restore a soft-deleted record.
 *
 * @property string|null $updatedAt Timestamp of the last update.
 * @property string|null $createdAt Timestamp of creation.
 */
abstract class Model implements \JsonSerializable
{
    use Model\HasAttributes {
        setAttribute as protected traitSetAttribute;
    }
    use Model\HasGuardAttributes;

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table;

    /**
     * Build a new, unguarded, unsaved model instance from trusted attributes.
     *
     * Bypasses mass-assignment protection and sanitizing entirely — for
     * internal/trusted data only (e.g. install-time setup). Nothing is
     * persisted; call save() on the result to actually write it.
     *
     * @param array<string, mixed>|stdClass $attributes Attributes to fill the model with.
     * @return static
     */
    public static function make(array|stdClass $attributes): static
    {
        $model = new static();

        $model->attributes = (array) $attributes;

        return $model;
    }

    /**
     * Create a new model instance from an array or stdClass and sync originals.
     *
     * @param array<string, mixed>|stdClass $attributes
     * @return static
     */
    public static function newFrom(array|stdClass $attributes): static
    {
        $model = new static();

        $model->setAttributes((array) $attributes)->syncOriginals();

        return $model;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array<string, mixed> $attributes
     * @return static
     * @throws Exception if attributes are not fillable
     */
    public function fill(array $attributes): static
    {
        if (!$attributes) {
            return $this;
        }

        if ($this->isTotallyGuarded()) {
            $keys = $this->fillable
                ? array_diff(array_keys($attributes), array_keys(array_flip($this->fillable)))
                : array_keys($attributes);

            throw new Exception(
                sprintf('Add [%s] to fillable property to allow mass assignment on [%s].', implode(", ", $keys), get_class($this))
            );
        }

        foreach ($attributes as $key => $val) {
            if (! $this->isFillable($key)) {
                continue;
            }

            $this->setAttribute($key, $val);
        }

        return $this;
    }

    /**
     * Sets the value of an attribute applying the defined sanitization rules.
     *
     * If a rule is defined for the attribute in $sanitize, it will be applied.
     * Supports static methods from the Safe class or callable rules.
     *
     * @param string $key   The attribute name
     * @param mixed  $value The value to set
     *
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $traits   = class_uses(static::class);
        $snakeKey = Str::snake($key);

        if (
            in_array(HasReadonlyAttributes::class, $traits, true) &&
            $this->isReadonly($snakeKey) &&
            isset($this->attributes[$snakeKey])
        ) {
            return $this;
        }

        if (in_array(HasSanitizing::class, $traits, true)) {
            $rule = $this->getSanitizerRules()[$snakeKey] ?? '';
            if ($rule) {
                // [$key => $value] goes first: for an attribute that's already
                // set, array + keeps the LEFT side on key collision, so this is
                // what makes the new value actually win instead of being
                // silently re-sanitized back to whatever was already stored.
                // The rest of $this->attributes is still merged in after, for
                // rules that reference a sibling field (e.g. 'slug:$login').
                $value = Safe::data([$key => $value] + $this->attributes, [$key => $rule])->apply($key);
            }
        }

        return $this->traitSetAttribute($key, $value);
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return Safe::snakecase($this->table);
    }

    /**
     * Get the model's attributes as a plain array.
     *
     * Overridden by Model\HasHiddenAttributes for models that need to keep
     * sensitive attributes (passwords, tokens, ...) out of this — getAttributes()
     * itself is never filtered, so persistence is unaffected either way.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Specify the data that should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Magic getter to access attributes.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    /**
     * Magic setter to set attributes.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Handle dynamic static method calls on the model class.
     *
     * This magic method intercepts static method calls that are not explicitly defined
     * in the model class. If the called method exists in the Query class, it delegates
     * the call to a new Query instance for the current model class, passing all arguments.
     * Otherwise, it throws an Exception.
     *
     * @param string $method    The name of the static method being called.
     * @param array  $arguments The arguments passed to the static method.
     *
     * @return mixed The result of the corresponding Query method call.
     *
     * @throws Exception If the method does not exist in the Query class.
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists(Query::class, $method)) {
            return new Query(new static())->$method(...$arguments);
        }
        throw new Exception("Method $method does not exist in " . static::class);
    }

    /**
     * Magic instance method handler.
     *
     * Delegates instance method calls to the Query class if method exists,
     * passing the current model instance.
     *
     * @param string $method    Method name called.
     * @param array  $arguments Arguments passed.
     * @return mixed
     *
     * @throws Exception When the called method does not exist in Query
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists(Query::class, $method)) {
            return new Query($this)->$method(...$arguments);
        }
        throw new Exception("Method $method does not exist in " . static::class);
    }
}
