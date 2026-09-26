<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * Helpers for arrays and lists of arrays or objects, nested keys use "dot" notation.
 */
class Arr
{
    /**
     * Chars that htmlspecialchars() escapes with ENT_QUOTES.
     */
    private const string HTML_SPECIAL = "&<>\"'";

    /**
     * Attributes rendered without a value, stored as keys for isset().
     */
    private const array BOOLEAN_ATTRIBUTES = [
        'accesskey'       => true,
        'async'           => true,
        'autofocus'       => true,
        'autoplay'        => true,
        'checked'         => true,
        'contenteditable' => true,
        'controls'        => true,
        'disabled'        => true,
        'draggable'       => true,
        'hidden'          => true,
        'ismap'           => true,
        'loop'            => true,
        'multiple'        => true,
        'readonly'        => true,
        'required'        => true,
        'selected'        => true,
    ];

    /**
     * Recursively removes empty values, including arrays that become empty.
     *
     * @param array $array
     * @return array
     */
    public static function clean(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[ $key ] = self::clean($value);
            }
        }
        return array_filter($array);
    }

    /**
     * Removes the elements with the listed keys.
     *
     * @param array $array
     * @param array $black_list Keys to remove.
     * @return array
     */
    public static function exclude(array $array, array $black_list): array
    {
        return array_diff_key($array, array_flip($black_list));
    }

    /**
     * Keeps only the elements with the listed keys.
     *
     * @param array $array
     * @param array $white_list Keys to keep.
     * @return array
     */
    public static function extract(array $array, array $white_list): array
    {
        return array_intersect_key($array, array_flip($white_list));
    }

    /**
     * Inserts items after the key, "dot" notation reaches nested arrays: 'menu.a' inserts into $array['menu'].
     * A literal key with dots wins over the path, a missing key returns the array unchanged.
     *
     * @param array      $array
     * @param int|string $position Key to insert after.
     * @param array      $insert   Merged as by array_merge(), int keys are renumbered.
     * @return array
     */
    public static function insert(array $array, int|string $position, array $insert): array
    {
        if (is_int($position) || array_key_exists($position, $array) || ! str_contains($position, '.')) {
            return self::insertAfter($array, $position, $insert);
        }

        $segments = explode('.', $position);
        $position = array_pop($segments);
        $target   = &$array;

        foreach ($segments as $segment) {
            if (! isset($target[ $segment ]) || ! is_array($target[ $segment ])) {
                return $array;
            }
            $target = &$target[ $segment ];
        }

        $target = self::insertAfter($target, $position, $insert);

        return $array;
    }

    /**
     * Moves the keys from the pattern to the start in its order, the rest keep their order.
     *
     * @param array $arr
     * @param array $pattern Keys in the wanted order, missing ones are skipped.
     * @return array
     */
    public static function sortByPattern(array $arr, array $pattern): array
    {
        return array_replace(array_intersect_key(array_flip($pattern), $arr), $arr);
    }

    /**
     * Sorts a list of arrays by the field value, int keys are renumbered.
     *
     * @param array  $array
     * @param string $field
     * @param int    $order SORT_ASC or SORT_DESC.
     * @return array
     */
    public static function sort(array $array, string $field, int $order = SORT_ASC): array
    {
        if (empty($array)) {
            return [];
        }

        array_multisort(array_column($array, $field), $order, $array);

        return $array;
    }

    /**
     * Recursive array_map() that keeps the keys, the callback gets only leaves.
     *
     * @param array    $array
     * @param callable $callback Called under strict_types.
     * @return array
     */
    public static function map(array $array, callable $callback): array
    {
        array_walk_recursive(
            $array,
            static function (&$v) use ($callback) {
                $v = $callback($v);
            }
        );
        return $array;
    }

    /**
     * Filters a list of arrays or objects by key => value pairs compared strictly, keys are kept.
     *
     * @param array  $array
     * @param array  $args     Pairs to match, empty returns the list as is.
     * @param string $operator 'AND' all pairs match, 'OR' any, 'NOT' none; another value returns the list as is.
     * @return array
     */
    public static function filter(array $array, array $args, string $operator = 'AND'): array
    {
        $operator = strtoupper($operator);
        if (empty($args) || ($operator !== 'AND' && $operator !== 'OR' && $operator !== 'NOT')) {
            return $array;
        }

        // a miss decides AND, a hit decides OR and NOT, only OR keeps the item on a decision
        $isAnd    = $operator === 'AND';
        $isOr     = $operator === 'OR';
        $filtered = [];

        foreach ($array as $key => $obj) {
            if (is_array($obj)) {
                foreach ($args as $m_key => $m_value) {
                    $hit = isset($obj[ $m_key ])
                        ? $obj[ $m_key ] === $m_value
                        : $m_value === null && array_key_exists($m_key, $obj);
                    if ($hit !== $isAnd) {
                        if ($isOr) {
                            $filtered[ $key ] = $obj;
                        }
                        continue 2;
                    }
                }
            } elseif (is_object($obj)) {
                foreach ($args as $m_key => $m_value) {
                    if ((isset($obj->{$m_key}) && $obj->{$m_key} === $m_value) !== $isAnd) {
                        if ($isOr) {
                            $filtered[ $key ] = $obj;
                        }
                        continue 2;
                    }
                }
            } elseif ($isAnd) {
                continue;
            }

            if (! $isOr) {
                $filtered[ $key ] = $obj;
            }
        }

        return $filtered;
    }

    /**
     * Renders escaped attributes with a leading space, e.g. ' type="text" required'.
     * Empty values are skipped except 'value' and 'u-*', boolean attributes render without a value.
     *
     * @param array $attributes
     * @return string
     */
    public static function toHtmlAtts(array $attributes): string
    {
        $atts = '';
        foreach ($attributes as $attribute => $value) {
            // escaping is inlined, a helper call per name and value costs ~15% here
            $attribute = (string) $attribute;
            $attribute = trim(strpbrk($attribute, self::HTML_SPECIAL) ? htmlspecialchars($attribute, ENT_QUOTES) : $attribute);
            if ($attribute === '') {
                continue;
            }

            $value = $value === true ? '1' : (string) $value;
            $value = trim(strpbrk($value, self::HTML_SPECIAL) ? htmlspecialchars($value, ENT_QUOTES) : $value);
            if (isset(self::BOOLEAN_ATTRIBUTES[ $attribute ])) {
                if ($value) {
                    $atts .= ' ' . $attribute;
                }
            } elseif ($value || $attribute === 'value') {
                $atts .= ' ' . $attribute . '="' . $value . '"';
            } elseif (str_starts_with($attribute, 'u-')) {
                $atts .= ' ' . $attribute;
            }
        }

        return $atts;
    }

    /**
     * Flattens nested arrays into "dot" keys: [ 'a' => [ 'b' => 1 ] ] becomes [ 'a.b' => 1 ].
     * Empty arrays are kept as values.
     *
     * @param iterable $array
     * @param string   $prepend Prefix for every key.
     * @return array
     */
    public static function dot(iterable $array, string $prepend = ''): array
    {
        $results = [];
        self::flatten($array, $prepend, $results);

        return $results;
    }

    /**
     * Expands "dot" keys into nested arrays, the reverse of dot(): [ 'a.b' => 1 ] becomes [ 'a' => [ 'b' => 1 ] ].
     *
     * @param iterable $array
     * @return array
     */
    public static function undot(iterable $array): array
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_int($key) || ! str_contains($key, '.')) {
                $results[ $key ] = $value;
                continue;
            }

            $keys   = explode('.', $key);
            $last   = array_pop($keys);
            $target = &$results;
            foreach ($keys as $segment) {
                if (! isset($target[ $segment ]) || ! is_array($target[ $segment ])) {
                    $target[ $segment ] = [];
                }
                $target = &$target[ $segment ];
            }
            $target[ $last ] = $value;
            unset($target);
        }
        return $results;
    }

    /**
     * Sets a value by "dot" key, missing and non-array levels on the way become arrays.
     *
     * @param array           $array
     * @param string|int|null $key   Null replaces the whole array.
     * @param mixed           $value
     * @return mixed The array level that received the value.
     */
    public static function set(&$array, string|int|null $key, mixed $value): mixed
    {
        if ($key === null) {
            return $array = $value;
        }

        if (is_int($key) || ! str_contains($key, '.')) {
            $array[ $key ] = $value;
            return $array;
        }

        $keys = explode('.', $key);
        $last = array_pop($keys);

        foreach ($keys as $segment) {
            if (! isset($array[ $segment ]) || ! is_array($array[ $segment ])) {
                $array[ $segment ] = [];
            }
            $array = &$array[ $segment ];
        }

        $array[ $last ] = $value;

        return $array;
    }

    /**
     * Gets a value by "dot" key, a literal key with dots wins over the path.
     *
     * @param array           $array
     * @param string|int|null $key     Null returns the whole array.
     * @param mixed           $default For a missing key, or a null value on the path.
     * @return mixed
     */
    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (isset($array[ $key ]) || array_key_exists($key, $array)) {
            return $array[ $key ];
        }

        if (is_int($key) || ! str_contains($key, '.')) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! isset($array[ $segment ])) {
                return $default;
            }
            $array = $array[ $segment ];
        }

        return $array;
    }

    /**
     * Inserts items after a key of this level, see insert().
     *
     * @param array      $array
     * @param int|string $position
     * @param array      $insert
     * @return array
     */
    private static function insertAfter(array $array, int|string $position, array $insert): array
    {
        if (! array_key_exists($position, $array)) {
            return $array;
        }

        // a numeric string key like '1' is stored as int, the strict search needs the stored form
        $position = array_key_first([ $position => 0 ]);
        $offset   = array_search($position, array_keys($array), true) + 1;

        return array_merge(array_slice($array, 0, $offset, true), $insert, array_slice($array, $offset, null, true));
    }

    /**
     * Worker of dot(), fills the result by reference instead of array_merge() on every level.
     *
     * @param iterable $array
     * @param string   $prepend
     * @param array    $results
     */
    private static function flatten(iterable $array, string $prepend, array &$results): void
    {
        foreach ($array as $key => $value) {
            if (is_array($value) && $value) {
                self::flatten($value, $prepend . $key . '.', $results);
            } else {
                $results[ $prepend . $key ] = $value;
            }
        }
    }
}
