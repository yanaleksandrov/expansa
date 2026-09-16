<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

/**
 * Trait HasGuardAttributes
 *
 * Provides functionality to protect model attributes from mass assignment.
 * You can define which attributes are fillable and which are guarded.
 *
 * Usage example - temporarily accept every key on fill() (and so on the constructor,
 * which calls fill() internally), bypassing $fillable/$guarded entirely, without also
 * skipping sanitizing/mutators the way {@see \Expansa\Database\Model::make()} does:
 *
 *     YourModel::$unguarded = true;
 *     $model = new YourModel($data);
 *     YourModel::$unguarded = false;
 *
 * $unguarded is per-class, not shared across models - each class using this trait gets
 * its own copy of the static property.
 */
trait HasGuardAttributes
{
    /**
     * Indicates if all attributes are mass assignable, regardless of $fillable/$guarded -
     * see the trait's own docblock for the intended usage.
     *
     * @var bool
     */
    public static bool $unguarded = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected array $guarded = ['*'];

    /**
     * List of attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected array $fillable = [];

    /**
     * Determine if the model is totally guarded (no fillable attributes and guarded is '*').
     *
     * Checked before $unguarded would otherwise apply per-key - without this override,
     * a model with no $fillable at all would still hard-throw out of fill() even with
     * $unguarded set, defeating the point of the flag.
     *
     * @return bool
     */
    protected function isTotallyGuarded(): bool
    {
        if (self::$unguarded) {
            return false;
        }

        return (empty($this->fillable) && $this->guarded === ['*']);
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        if (self::$unguarded) {
            return true;
        }

        if (in_array($key, $this->guarded)) {
            return false;
        }

        return in_array($key, $this->fillable);
    }

    /**
     * Determine if the given attribute is guarded.
     *
     * @param string $key
     * @return bool
     */
    protected function isGuarded(string $key): bool
    {
        if (empty($this->guarded)) {
            return false;
        }

        return ($this->guarded === ['*']) || in_array($key, $this->guarded);
    }

    /**
     * Get the fillable attributes for the model.
     *
     * @return array<string>
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }
}
