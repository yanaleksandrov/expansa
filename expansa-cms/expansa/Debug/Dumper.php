<?php

declare(strict_types=1);

namespace Expansa\Debug;

use Closure;
use UnitEnum;

/**
 * Readable dump of values for dump() and dd(): nested arrays and objects with their private and
 * protected properties, cut at a depth and on recursion. Plain text in the console, an escaped
 * `<pre>` in the browser.
 *
 * Object properties are marked by visibility: `+public`, `#protected`, `-private`.
 *
 * @package Expansa\Debug
 */
final class Dumper
{
    /**
     * Nesting shown in full; deeper arrays and objects show only their size or class.
     */
    private const int DEPTH = 6;

    /**
     * Characters of a string shown before it is cut.
     */
    private const int STRING_LENGTH = 1000;

    public function __construct(

        /**
         * Output plain text instead of HTML.
         */
        private readonly bool $console = PHP_SAPI === 'cli',
    ) {}

    /**
     * Output the values, each in its own block.
     *
     * @param mixed ...$values
     * @return void
     */
    public function dump(mixed ...$values): void
    {
        foreach ($values as $value) {
            $text = $this->render($value);

            echo $this->console
                ? $text . PHP_EOL
                : '<pre class="debug-dump">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }

    /**
     * Readable text of a value.
     *
     * @param mixed $value
     * @return string
     */
    public function render(mixed $value): string
    {
        $seen = [];

        return $this->value($value, 0, $seen);
    }

    /**
     * Text of a value at a depth of nesting.
     *
     * @param mixed            $value
     * @param int              $depth
     * @param array<int, true> $seen  Ids of the objects being dumped, to stop on recursion.
     * @return string
     */
    private function value(mixed $value, int $depth, array &$seen): string
    {
        return match (true) {
            $value === null         => 'null',
            is_bool($value)         => $value ? 'true' : 'false',
            is_int($value),
            is_float($value)        => var_export($value, true),
            is_string($value)       => $this->string($value),
            is_array($value)        => $this->array($value, $depth, $seen),
            $value instanceof UnitEnum => $value::class . '::' . $value->name,
            $value instanceof Closure  => 'Closure',
            is_object($value)       => $this->object($value, $depth, $seen),
            default                 => get_debug_type($value),
        };
    }

    /**
     * Quoted string, cut after STRING_LENGTH characters, with its length.
     *
     * @param string $value
     * @return string
     */
    private function string(string $value): string
    {
        $length = mb_strlen($value);
        $shown  = $length > self::STRING_LENGTH ? mb_substr($value, 0, self::STRING_LENGTH) . '…' : $value;

        return '"' . addcslashes($shown, "\"\\\0..\37") . '" (' . $length . ')';
    }

    /**
     * Array with its keys, one item per line.
     *
     * @param array<array-key, mixed> $value
     * @param int                     $depth
     * @param array<int, true>        $seen
     * @return string
     */
    private function array(array $value, int $depth, array &$seen): string
    {
        $head = 'array(' . count($value) . ')';
        if ($value === []) {
            return $head . ' []';
        }

        if ($depth >= self::DEPTH) {
            return $head . ' […]';
        }

        $items = [];
        foreach ($value as $key => $item) {
            $items[] = var_export($key, true) . ' => ' . $this->value($item, $depth + 1, $seen);
        }

        return $head . ' ' . $this->block('[', $items, ']', $depth);
    }

    /**
     * Object with its properties, __debugInfo() when it has one.
     *
     * @param object           $value
     * @param int              $depth
     * @param array<int, true> $seen
     * @return string
     */
    private function object(object $value, int $depth, array &$seen): string
    {
        $id   = spl_object_id($value);
        $head = get_debug_type($value) . ' #' . $id;

        if (isset($seen[$id])) {
            return $head . ' {recursion}';
        }

        if ($depth >= self::DEPTH) {
            return $head . ' {…}';
        }

        $properties = method_exists($value, '__debugInfo') ? (array) $value->__debugInfo() : (array) $value;
        if ($properties === []) {
            return $head . ' {}';
        }

        $seen[$id] = true;

        $items = [];
        foreach ($properties as $name => $property) {
            $items[] = $this->property((string) $name) . ': ' . $this->value($property, $depth + 1, $seen);
        }

        unset($seen[$id]);

        return $head . ' ' . $this->block('{', $items, '}', $depth);
    }

    /**
     * Property name with its visibility, from the key of an array cast: "\0Class\0name", "\0*\0name".
     *
     * @param string $key
     * @return string
     */
    private function property(string $key): string
    {
        if (! str_starts_with($key, "\0")) {
            return '+' . $key;
        }

        // the class of an anonymous class has "\0" in it too, so the name is after the last one
        return (str_starts_with($key, "\0*\0") ? '#' : '-') . substr($key, strrpos($key, "\0") + 1);
    }

    /**
     * Items on their own lines, indented by depth.
     *
     * @param string   $open
     * @param string[] $items
     * @param string   $close
     * @param int      $depth
     * @return string
     */
    private function block(string $open, array $items, string $close, int $depth): string
    {
        $indent = str_repeat('  ', $depth + 1);

        return $open . PHP_EOL . $indent . implode(PHP_EOL . $indent, $items) . PHP_EOL . str_repeat('  ', $depth) . $close;
    }
}
