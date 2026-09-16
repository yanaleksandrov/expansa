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
 * @method static bool        exists(array $data)                  Check record is existing.
 * @method null|static        save()                               Insert or update the record and return the fresh model.
 * @method int                delete()                             Delete the record by primary key.
 * @method int                restore()                            Restore a soft-deleted record.
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
     * Whether this model's class, or any ancestor, `use`s $trait. Unlike a bare class_uses()
     * call (which only sees traits used directly by the exact class, not inherited ones), this
     * walks the full parent chain, and caches the result - a class's traits never change at runtime.
     */
    public function usesTrait(string $trait): bool
    {
        // Scoped to this method only - no other method reads or resets this cache. Keyed by
        // static::class since this one method body is shared by every Model subclass.
        static $cache = [];

        return $cache[static::class][$trait] ??= array_any(
            [static::class, ...(class_parents(static::class) ?: [])],
            fn($class) => in_array($trait, class_uses($class), true)
        );
    }

    /**
     * Build a new, unsaved instance, optionally filled with the given attributes -
     * sanitized and passed through each attribute's mutator, exactly like calling
     * {@see self::fill()} on an empty instance (which is exactly what this does).
     * `new User($data)` is the "new + fill" idiom; call save() on the result to persist it.
     *
     * @param array<string, mixed> $attributes
     * @throws Exception if attributes are not fillable
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Build a new model instance from trusted attributes, bypassing mass-assignment
     * protection and sanitizing/mutators entirely - for internal/trusted data only
     * (a value already in its final form: a hashed password, a deduped nicename, a
     * row just read from the database, ...), never raw user input. See {@see self::fill()}
     * for the opposite - the one to reach for whenever the data didn't originate in
     * your own trusted code.
     *
     * A truthy 'id' in $attributes is treated as "this row already exists in the
     * database" and syncs originals accordingly, so a later save() diffs against
     * these values (an update with nothing actually changed becomes a no-op)
     * instead of treating every attribute as dirty. This is what Query::get()/
     * find()/first()/all() rely on to hydrate rows. Without an 'id', the instance
     * is treated as brand new and unsaved - call save() to insert it.
     *
     * @param array<string, mixed>|stdClass $attributes Attributes to fill the model with.
     * @return static
     */
    public static function make(array|stdClass $attributes): static
    {
        $model = new static();

        $model->attributes = (array) $attributes;

        if (! empty($model->attributes['id'])) {
            $model->syncOriginals();
        }

        return $model;
    }

    /**
     * Mass-assign the given attributes - sanitized and passed through each attribute's
     * mutator (e.g. User's password gets hashed) - unlike {@see self::make()}, which
     * treats the data as already-final and skips both. This is the one to use for raw
     * user/API input.
     *
     * Always an instance method - there's no separate static entry point. For a brand
     * new record, construct one and let the constructor call this for you: `new User($data)`
     * (see {@see self::__construct()}). To mass-assign onto a model that already exists -
     * `$this` inside an update()-style method, or one already fetched from the database -
     * call `$model->fill($data)` directly; it mutates that same instance in place.
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
     * Sets $key, applying its sanitizer rule (see {@see Model\HasSanitizing::getSanitizerRules()})
     * and mutator (see {@see Model\Attribute}), if either is declared. No-ops if $key is
     * readonly and already has a value (see {@see Model\HasReadonlyAttributes}).
     *
     * @param string $key   The attribute name
     * @param mixed  $value The value to set
     *
     * @return static
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $snakeKey = Str::snake($key);

        if (
            $this->usesTrait(HasReadonlyAttributes::class)
            &&
            $this->isReadonly($snakeKey)
            &&
            isset($this->attributes[$snakeKey])
        ) {
            return $this;
        }

        if ($this->usesTrait(HasSanitizing::class)) {
            $rule = $this->sanitizerRules()[$snakeKey] ?? '';
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
     * Get the table associated with the model. Cached per class: $table is a fixed class
     * property (never reassigned per-instance by any Model subclass), but Safe::snakecase()
     * itself runs 3 preg_replace passes with no memoization of its own, and this is called
     * repeatedly per Query call (Query::get()/find()/save()/... each read it 1-2x).
     *
     * @return string
     */
    public function getTable(): string
    {
        // Scoped to this method only - no other method reads or resets this cache. Keyed by
        // static::class since this one method body is shared by every Model subclass.
        static $cache = [];

        return $cache[static::class] ??= Safe::snakecase($this->table);
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
