<?php

declare(strict_types=1);

namespace Expansa\Database\Traits;

/**
 * Mass assignment protection: fill() and the constructor set only the fillable attributes.
 *
 * To accept every key for a while, still with sanitizers and mutators, unlike Model::hydrate():
 *
 *     YourModel::$unguarded = true;
 *     $model = new YourModel($data);
 *     YourModel::$unguarded = false;
 *
 * @package Expansa\Database\Traits
 */
trait HasGuardAttributes
{
    /**
     * Every attribute is mass assignable, regardless of $fillable and $guarded.
     */
    public static bool $unguarded = false;

    /**
     * Attributes that are never mass assignable, `*` guards all but the fillable ones.
     *
     * @var string[]
     */
    protected array $guarded = ['*'];

    /**
     * Mass assignable attributes.
     *
     * @var string[]
     */
    public protected(set) array $fillable = [];

    /**
     * Whether nothing is fillable; checked before $unguarded applies per key, so it must honour the flag too.
     *
     * @return bool
     */
    protected function isTotallyGuarded(): bool
    {
        return ! self::$unguarded && ! $this->fillable && $this->guarded === ['*'];
    }

    protected function isFillable(string $key): bool
    {
        if (self::$unguarded) {
            return true;
        }

        return ! in_array($key, $this->guarded, true) && in_array($key, $this->fillable, true);
    }

    protected function isGuarded(string $key): bool
    {
        return $this->guarded === ['*'] || in_array($key, $this->guarded, true);
    }
}
