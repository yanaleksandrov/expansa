<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

/**
 * Trait HasGuardAttributes
 *
 * Provides functionality to protect model attributes from mass assignment.
 * You can define which attributes are fillable and which are guarded.
 *
 * Usage example:
 *
 *     YourModel::$unguarded = true;
 *     YourModel::create($data);
 *     YourModel::$unguarded = false;
 */
trait HasGuardAttributes
{
    /**
     * Indicates if all attributes are mass assignable.
     *
     * @var bool
     */
    protected static bool $unguarded = false;

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
     * @return bool
     */
    protected function isTotallyGuarded(): bool
    {
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
