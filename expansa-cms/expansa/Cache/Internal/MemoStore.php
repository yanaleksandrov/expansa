<?php

declare(strict_types=1);

namespace Expansa\Cache\Internal;

/**
 * The memo of one provider instance, see Traits\Memoizes.
 *
 * An object, not an array in the WeakMap: an array would be copied on every write,
 * making N writes O(N²); the object's array is changed in place.
 *
 * @internal
 * @package Expansa\Cache\Internal
 */
final class MemoStore
{
    /**
     * Values by group and key, wrapped to tell a cached null from a missing key.
     *
     * @var array<string, array<string, array{value: mixed}>>
     */
    public array $data = [];
}
