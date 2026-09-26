<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use Closure;
use Expansa\Hooks\Attributes\AttributesReader;
use ReflectionException;

/**
 * Storage layer for {@see Manager}: keeps registered listeners, builds their identifiers and
 * hands them back sorted by priority, caching the sorted order until listeners for that hook change.
 */
abstract class HooksCollector
{
    use AttributesReader;

    /**
     * All registered listeners, keyed by hook name and then by listener id.
     *
     * @var array<string, array<string, array{key: string, function: callable, source: array{file: string, line: int|string}, priority: int}>>
     */
    protected static array $hooks = [];

    /**
     * Priority-sorted listeners per hook name, memoized until {@see self::$hooks} changes for that name.
     *
     * @var array<string, list<array{key: string, function: callable, source: array{file: string, line: int|string}, priority: int}>>
     */
    protected static array $sorted = [];

    /**
     * Returns a unique id for a given hook name and function.
     *
     * This is used as a unique identifier for flush(). A closure carrying #[HookListenerAlias]
     * is identified by its alias instead of its object id, so flush() can later target it by
     * that same alias string - otherwise anonymous functions have no stable identity to remove by.
     *
     * @param string                $hookName Name of hook.
     * @param string|array|callable $function String with function name, closure, or [object|class, method] array.
     *
     * @return string
     * @throws ReflectionException
     */
    protected function makeId(string $hookName, string|array|callable $function): string
    {
        $identity = match (true) {
            is_string($function)                  => $function,
            $function instanceof Closure          => $this->getAlias($function) ?? (string) spl_object_id($function),
            is_object($function)                  => (string) spl_object_id($function),
            is_object($function[0] ?? null) => spl_object_id($function[0]) . '::' . $function[1],
            default                               => implode('::', $function),
        };

        return hash('xxh3', $hookName . '::' . $identity);
    }

    /**
     * Returns the listeners of a given hook name, sorted by priority in ascending order.
     *
     * The sorted order is cached and reused until a listener is added to or removed from that
     * hook name, since sorting on every {@see Manager::call()} would otherwise repeat the same
     * work for hooks that fire many times per request.
     *
     * @param string $name Name of hook.
     *
     * @return list<array{key: string, function: callable, source: array{file: string, line: int|string}, priority: int}>
     */
    protected function sortedHooks(string $name): array
    {
        return self::$sorted[$name] ??= self::multisort(array_values(self::$hooks[$name] ?? []), 'priority');
    }

    /**
     * Sort a multidimensional array by a given key in ascending (optionally, descending) order.
     *
     * @param array  $array      Original array.
     * @param string $key        Key name to sort by.
     * @param bool   $descending Sort descending.
     *
     * @return array
     */
    public static function multisort(array $array, string $key, bool $descending = false): array
    {
        // Picking the comparator once, instead of branching on $descending inside it, keeps that
        // branch out of every pairwise comparison usort() makes.
        $comparator = $descending
            ? static fn (array $a, array $b): int => $b[$key] <=> $a[$key]
            : static fn (array $a, array $b): int => $a[$key] <=> $b[$key];

        usort($array, $comparator);

        return $array;
    }
}
