<?php

declare(strict_types=1);

namespace Expansa\Database;

use BadMethodCallException;
use Closure;
use Expansa\Database\Internal\Cache;
use Expansa\Database\Traits\HasAttributes;
use Expansa\Database\Traits\HasGuardAttributes;
use Expansa\Database\Traits\HasReadonlyAttributes;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Support\Str;
use JsonSerializable;
use LogicException;
use stdClass;

/**
 * Base model: attributes with mutators, mass assignment protection, and Query methods called on the model.
 * Caching, sanitizing and validation come from configure(), bootstrap.php wires them to the Cache and Security packages.
 *
 * @method static static|null get(int|string $value, string $by = 'id') Find a model by primary key or another field.
 * @method static static[]    find()                                    Get the models matching the where conditions.
 * @method static static|null first()                                   Get the first model matching the where conditions.
 * @method static static[]    all()                                     Get every model.
 * @method static Query       where(array $args)                        Start a query with where conditions.
 * @method static bool        exists(array $data)                       Whether a row matches any of the fields.
 * @method null|static        save()                                    Insert or update the row.
 * @method int                delete()                                  Delete the row, or soft-delete it.
 * @method int                restore()                                 Restore a soft-deleted row.
 *
 * @package Expansa\Database
 */
abstract class Model implements JsonSerializable
{
    use HasAttributes {
        setAttribute as protected traitSetAttribute;
    }
    use HasGuardAttributes;

    /**
     * Table of the model; also the cache group of its rows.
     */
    public protected(set) string $table;

    /**
     * Sanitizes by rules: (array $data, array $rules): array of the sanitized values by key.
     */
    protected static ?Closure $sanitizer = null;

    /**
     * Creates a validator: (array $data, array $rules, bool $break): object with apply(), isValid(), getErrors().
     */
    protected static ?Closure $validatorFactory = null;

    /**
     * Create an unsaved model and fill it: sanitizers and mutators apply, unfillable keys are skipped.
     *
     * @param array<string, mixed> $attributes
     * @throws LogicException If the model has no fillable attributes.
     */
    final public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Set the services of the models, a repeated call replaces all of them.
     *
     * @param Closure|null $cache       Rows and fields cache: (string $key, string $group, ?Closure $callback): mixed,
     *                                  on a miss stores and returns the callback result. Nothing is cached without it.
     * @param Closure|null $forgetCache (string $key, string $group): mixed.
     * @param Closure|null $sanitizer   (array $data, array $rules): array, required by HasSanitizing.
     * @param Closure|null $validator   (array $data, array $rules, bool $break): object, required by HasValidation.
     * @return void
     */
    public static function configure(
        ?Closure $cache = null,
        ?Closure $forgetCache = null,
        ?Closure $sanitizer = null,
        ?Closure $validator = null,
    ): void {
        Cache::configure($cache, $forgetCache);

        self::$sanitizer        = $sanitizer;
        self::$validatorFactory = $validator;
    }

    /**
     * Create a model from trusted data, such as a database row: no mass assignment check, sanitizers or mutators.
     * With an id the model counts as saved, so save() writes only the changed attributes.
     *
     * @param array<string, mixed>|stdClass $attributes
     * @return static
     */
    public static function hydrate(array|stdClass $attributes): static
    {
        $model = new static();

        $model->attributes = (array) $attributes;

        if (! empty($model->attributes['id'])) {
            $model->syncOriginals();
        }

        return $model;
    }

    /**
     * Whether the class or a parent uses the trait, cached per class.
     *
     * @param class-string $trait
     * @return bool
     */
    public function usesTrait(string $trait): bool
    {
        static $cache = [];

        return $cache[static::class][$trait] ??= array_any(
            [static::class, ...(class_parents(static::class) ?: [])],
            fn ($class) => in_array($trait, class_uses($class), true)
        );
    }

    /**
     * Mass-assign raw input: sanitizers and mutators apply, unfillable keys are skipped.
     *
     * @param array<string, mixed> $attributes
     * @return static
     * @throws LogicException If the model has no fillable attributes.
     */
    public function fill(array $attributes): static
    {
        if (! $attributes) {
            return $this;
        }

        if ($this->isTotallyGuarded()) {
            $keys = implode(', ', array_diff(array_keys($attributes), $this->fillable));

            throw new LogicException(sprintf('Add [%s] to fillable property to allow mass assignment on [%s].', $keys, static::class));
        }

        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Set an attribute through its sanitizer rule and mutator; a readonly attribute that has a value is kept.
     *
     * @param string $key
     * @param mixed  $value
     * @return static
     * @throws LogicException If the attribute has a sanitizer rule and no sanitizer is configured.
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $snakeKey = Str::snake($key);

        if ($this->usesTrait(HasReadonlyAttributes::class) && $this->isReadonly($snakeKey) && isset($this->attributes[$snakeKey])) {
            return $this;
        }

        $rule = $this->usesTrait(HasSanitizing::class) ? $this->sanitizerRules()[$snakeKey] ?? '' : '';
        if ($rule !== '') {
            $sanitizer = self::$sanitizer ?? throw new LogicException('Sanitizing rules of ' . static::class . ' need Model::configure(sanitizer: ...).');

            // the new value goes first, + keeps the left one; other attributes serve rules like 'slug:$login'
            $value = $sanitizer([$key => $value] + $this->attributes, [$key => $rule])[$key] ?? null;
        }

        return $this->traitSetAttribute($key, $value);
    }

    /**
     * Get the attributes for arrays and JSON, HasHiddenAttributes removes the hidden ones.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Call a Query method on a new query of the model: `User::get(1)`.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws BadMethodCallException If Query has no such method.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        if (method_exists(Query::class, $method)) {
            return new Query(new static())->$method(...$arguments);
        }

        throw new BadMethodCallException("Method $method does not exist in " . static::class);
    }

    /**
     * Call a Query method on a query of this model: `$user->save()`.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws BadMethodCallException If Query has no such method.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (method_exists(Query::class, $method)) {
            return new Query($this)->$method(...$arguments);
        }

        throw new BadMethodCallException("Method $method does not exist in " . static::class);
    }
}
