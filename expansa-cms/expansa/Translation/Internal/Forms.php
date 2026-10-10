<?php

declare(strict_types=1);

namespace Expansa\Translation\Internal;

use Closure;

/**
 * Blocks of plural forms in a string: `:count {file|files}`, `{# file|# files}`, `{=0 No files|# file|# files}`.
 * Forms go in the order of the plural rule of the language, `=N` gives the text for exactly N, `#` is the number;
 * `\{` and `\}` are literal braces.
 *
 * @internal
 * @package Expansa\Translation
 */
final class Forms
{
    /**
     * A block of forms in braces that are not escaped with a backslash.
     */
    private const string BLOCK = '/(?<!\\\\)\{((?:[^{}\\\\]|\\\\.)*)\}/';

    /**
     * Parsed strings: text and blocks in turn, a block as the texts for exact counts and the forms.
     *
     * @var array<string, array<int, string|array{array<int, string>, string[]}>>
     */
    private static array $parsed = [];

    /**
     * Whether the string may have a block of forms: braces are reserved for them.
     *
     * @param string $string
     * @return bool
     */
    public static function has(string $string): bool
    {
        return str_contains($string, '{');
    }

    /**
     * Replace every block with its form for the count and `#` in it with the number; unescape braces.
     *
     * @param string            $string
     * @param int               $count
     * @param Closure(int): int $rule   Plural rule of the language: index of the form for a count.
     * @param string            $number The count formatted for the locale.
     * @return string
     */
    public static function choose(string $string, int $count, Closure $rule, string $number): string
    {
        $result = '';
        foreach (self::$parsed[$string] ??= self::parse($string) as $part) {
            if (is_string($part)) {
                $result .= $part;
                continue;
            }

            [$exact, $forms] = $part;
            $form            = $exact[$count] ?? $forms[$rule(abs($count))] ?? end($forms) ?: '';
            $result         .= str_replace('#', $number, $form);
        }

        return $result;
    }

    /**
     * Get the number of plural forms in every block, `=N` cases aside: a translation needs as many as its language.
     *
     * @param string $string
     * @return int[]
     */
    public static function count(string $string): array
    {
        $blocks = array_filter(self::$parsed[$string] ??= self::parse($string), is_array(...));

        return array_values(array_map(fn (array $block) => count($block[1]), $blocks));
    }

    /**
     * Split a string into text with unescaped braces and blocks.
     *
     * @param string $string
     * @return array<int, string|array{array<int, string>, string[]}>
     */
    private static function parse(string $string): array
    {
        $parts = preg_split(self::BLOCK, $string, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$string];

        // even parts are the text around the blocks, odd ones the blocks
        foreach ($parts as $i => $part) {
            $parts[$i] = $i % 2 === 0 ? str_replace(['\\{', '\\}'], ['{', '}'], $part) : self::split($part);
        }

        return $parts;
    }

    /**
     * Split a block into the texts for exact counts and the plural forms.
     *
     * @param string $block
     * @return array{array<int, string>, string[]}
     */
    private static function split(string $block): array
    {
        $exact = [];
        $forms = [];
        foreach (explode('|', $block) as $form) {
            $space = strpos($form, ' ');
            if ($form !== '' && $form[0] === '=' && $space !== false && ctype_digit(substr($form, 1, $space - 1))) {
                $exact[(int) substr($form, 1, $space - 1)] = substr($form, $space + 1);
            } else {
                $forms[] = $form;
            }
        }

        return [$exact, $forms];
    }
}
