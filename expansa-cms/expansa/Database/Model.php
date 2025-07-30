<?php

declare(strict_types=1);

namespace Expansa\Database;

use Exception;
use Expansa\Database\Model\HasAttributes;
use Expansa\Database\Model\HasGuardAttributes;
use Expansa\Database\Model\HasTimestamps;
use Expansa\Support\Str;
use stdClass;

/**
 * Base data model class with support for attributes, mass assignment protection,
 * timestamps, and soft deletes.
 *
 * @property string|null $updatedAt Timestamp of the last update
 * @property string|null $createdAt Timestamp of creation
 * @property string|null $deletedAt Timestamp of soft deletion
 */
class Model
{
    use HasAttributes;
    use HasGuardAttributes;
    use HasTimestamps;

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
    public static function create(array|stdClass $attributes): static
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
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return Str::snake($this->table);
    }
}
