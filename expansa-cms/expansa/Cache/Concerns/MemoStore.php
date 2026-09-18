<?php

declare(strict_types=1);

namespace Expansa\Cache\Concerns;

/**
 * A single provider instance's L1 memo, held as a mutable object rather than a plain array.
 *
 * Memoizes stores exactly one of these per instance in its WeakMap. Storing a plain array
 * directly in the WeakMap instead would force every write through a "read the whole array out,
 * mutate the copy, write the whole array back in" cycle — and since PHP arrays are
 * copy-on-write value types, that cycle duplicates the entire (ever-growing) array on every
 * single memoize() call, turning N writes to N distinct keys into O(N²) work. An object is a
 * handle: fetched once from the WeakMap, its $data array is then mutated in place like any other
 * object property, at the normal O(1) amortized cost.
 */
final class MemoStore
{
    /**
     * @var array<string, array<string, array{value: mixed}>>
     */
    public array $data = [];
}
