<?php

declare(strict_types=1);

namespace Expansa\Database;

use Exception;
use Expansa\Database\Model\HasAttributes;
use Expansa\Database\Model\HasGuardAttributes;
use Expansa\Support\Str;
use stdClass;

/**
 * Base data model class with support for attributes, mass assignment protection,
 * timestamps, and soft deletes.
 *
 * @method static static|null find(int|string $value, string $by = 'id') Find a model by primary key or specified field.
 * @method static static|null add(array $attributes)                     Create a new record.
 * @method int                delete()                                   Delete records by primary key.
 *
 * @property string|null $updatedAt Timestamp of the last update.
 * @property string|null $createdAt Timestamp of creation.
 */
class Model
{
    use HasAttributes;
    use HasGuardAttributes;

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table;

    /**
     * List of attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * List of attributes hidden from serialization.
     *
     * @var array<int, string>
     */
    protected array $hidden = [];

    /**
     * Array of model attributes.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Model constructor.
     *
     * @param array<string, mixed>|stdClass $attributes Initial attributes.
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Create a new model instance with the given attributes.
     *
     * @param array<string, mixed>|stdClass $attributes Attributes to fill the model with.
     * @return static
     */
    protected static function create(array|stdClass $attributes): static
    {
        return new static($attributes);
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
        if (empty($attributes)) {
            return $this;
        }

        $error = function (string|array $keys) {
            throw new Exception(sprintf(
                'Add [%s] to fillable property to allow mass assignment on [%s].',
                implode(", ", (array) $keys),
                get_class($this)
            ));
        };

        if ($this->totallyGuarded()) {
            $error(
                (count($this->fillable) === 0)
                    ? array_keys($attributes)
                    : array_diff(array_keys($attributes), array_keys(array_flip($this->fillable)))
            );
        }

        foreach ($attributes as $key => $val) {
            if (! $this->isFillable($key)) {
                $error($key);
            }

            $this->setAttribute($key, $val);
        }

        return $this;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return Str::snake($this->table);
    }

    public function getFillable(): array
    {
        return $this->fillable;
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
            return (new Query(new static()))->$method(...$arguments);
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
            return (new Query($this))->$method(...$arguments);
        }
        throw new Exception("Method $method does not exist in " . static::class);
    }
}
