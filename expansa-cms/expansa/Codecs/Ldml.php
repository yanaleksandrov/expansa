<?php

declare(strict_types=1);

namespace Expansa\Codecs;

use InvalidArgumentException;

/**
 * Converts date formats between PHP date() and Unicode LDML patterns (date-fns, Luxon, ICU, IntlDateFormatter).
 * By default parts without an exact equivalent are approximated or dropped; strict mode throws instead.
 *
 * @package Expansa\Codecs
 */
class Ldml
{
    /**
     * PHP date() tokens with an exact LDML equivalent.
     */
    private const array PHP_TO_LDML = [
        'd' => 'dd',
        'D' => 'EEE',
        'j' => 'd',
        'l' => 'EEEE',
        'F' => 'MMMM',
        'm' => 'MM',
        'M' => 'MMM',
        'n' => 'M',
        'Y' => 'yyyy',
        'y' => 'yy',
        'A' => 'a',
        'g' => 'h',
        'G' => 'H',
        'h' => 'hh',
        'H' => 'HH',
        'i' => 'mm',
        's' => 'ss',
        'v' => 'SSS',
        'u' => 'SSSSSS',
        'e' => 'VV',
        'O' => 'xx',
        'P' => 'xxx',
        'p' => 'XXX',
        'c' => "yyyy-MM-dd'T'HH:mm:ssxxx",
        'r' => 'EEE, dd MMM yyyy HH:mm:ss xx',
    ];

    /**
     * PHP date() tokens with only an approximate LDML equivalent.
     */
    private const array PHP_FALLBACKS = [
        'a' => 'a',    // LDML has no lowercase am/pm
        'T' => 'z',    // ICU prints "GMT+3" instead of "MSK"
        'W' => 'ww',   // LDML weeks follow the locale rules, not ISO 8601
        'o' => 'YYYY',
    ];

    /**
     * Letters with a meaning in PHP date(); any other character is printed as is.
     */
    private const string PHP_TOKENS = 'dDjlNSwzWFmMntLoXxYyaABgGhHisuveIOPpTZcrU';

    /**
     * LDML fields with an exact PHP date() equivalent; every other letter run is an unsupported field.
     */
    private const array LDML_TO_PHP = [
        'y'      => 'Y',
        'yy'     => 'y',
        'yyy'    => 'Y',
        'yyyy'   => 'Y',
        'M'      => 'n',
        'MM'     => 'm',
        'MMM'    => 'M',
        'MMMM'   => 'F',
        'L'      => 'n',
        'LL'     => 'm',
        'LLL'    => 'M',
        'LLLL'   => 'F',
        'd'      => 'j',
        'dd'     => 'd',
        'E'      => 'D',
        'EE'     => 'D',
        'EEE'    => 'D',
        'EEEE'   => 'l',
        'eee'    => 'D',
        'eeee'   => 'l',
        'ccc'    => 'D',
        'cccc'   => 'l',
        'a'      => 'A',
        'aa'     => 'A',
        'aaa'    => 'A',
        'h'      => 'g',
        'hh'     => 'h',
        'H'      => 'G',
        'HH'     => 'H',
        'mm'     => 'i',
        'ss'     => 's',
        'SSS'    => 'v',
        'SSSSSS' => 'u',
        'VV'     => 'e',
        'xx'     => 'O',
        'xxx'    => 'P',
        'XXX'    => 'p',
        'Z'      => 'O',
        'ZZ'     => 'O',
        'ZZZ'    => 'O',
        'ZZZZZ'  => 'p',
    ];

    /**
     * LDML fields with only an approximate PHP date() equivalent.
     */
    private const array LDML_FALLBACKS = [
        'm'    => 'i', // PHP has no minutes without a leading zero
        's'    => 's',
        'z'    => 'T',
        'zz'   => 'T',
        'zzz'  => 'T',
        'w'    => 'W',
        'ww'   => 'W',
        'Y'    => 'o',
        'YYYY' => 'o',
    ];

    /**
     * Converts a PHP date() format to an LDML pattern, e.g. "d.m.Y H:i" to "dd.MM.yyyy HH:mm".
     *
     * @param string $format PHP date() format.
     * @param bool   $strict Throw instead of approximating or dropping tokens without an exact equivalent.
     * @return string
     * @throws InvalidArgumentException In strict mode, listing every token that cannot be converted exactly.
     */
    public function encode(string $format, bool $strict = false): string
    {
        $parts  = [];
        $errors = [];
        $chars  = mb_str_split($format);
        $count  = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];

            if ($char === '\\') {
                $this->addLiteral($parts, $chars[++$i] ?? '');
            } elseif (isset(self::PHP_TO_LDML[$char])) {
                $parts[] = [true, self::PHP_TO_LDML[$char]];
            } elseif (str_contains(self::PHP_TOKENS, $char)) {
                $errors[] = sprintf('PHP token "%s" has no exact LDML equivalent', $char);
                $parts[]  = [true, self::PHP_FALLBACKS[$char] ?? null];
            } else {
                $this->addLiteral($parts, $char);
            }
        }

        return $this->render($parts, $errors, $strict, $this->quoteLdml(...));
    }

    /**
     * Converts an LDML pattern to a PHP date() format, e.g. "d MMMM, yyyy 'at' HH:mm" to "j F, Y \a\t H:i".
     *
     * @param string $format LDML pattern.
     * @param bool   $strict Throw instead of approximating or dropping fields without an exact equivalent.
     * @return string
     * @throws InvalidArgumentException In strict mode, listing every field that cannot be converted exactly.
     */
    public function decode(string $format, bool $strict = false): string
    {
        $parts  = [];
        $errors = [];
        $chars  = mb_str_split($format);
        $count  = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];

            if ($char === "'") {
                $this->addLiteral($parts, $this->readQuoted($chars, $i));
            } elseif (preg_match('/[a-zA-Z]/', $char)) {
                $field = $char;
                while (($chars[$i + 1] ?? '') === $char) {
                    $field .= $chars[++$i];
                }

                if (isset(self::LDML_TO_PHP[$field])) {
                    $parts[] = [true, self::LDML_TO_PHP[$field]];
                } else {
                    $errors[] = sprintf('LDML field "%s" has no exact PHP equivalent', $field);
                    $parts[]  = [true, self::LDML_FALLBACKS[$field] ?? null];
                }
            } else {
                $this->addLiteral($parts, $char);
            }
        }

        return $this->render($parts, $errors, $strict, $this->escapePhp(...));
    }

    /**
     * Appends text to the parts, merging it with a preceding literal.
     *
     * @param array<int, array{0: bool, 1: string|null}> $parts [is token, value]; a null token is dropped.
     * @param string                                     $text
     * @return void
     */
    private function addLiteral(array &$parts, string $text): void
    {
        $last = array_key_last($parts);

        if ($last !== null && $parts[$last][0] === false) {
            $parts[$last][1] .= $text;
        } else {
            $parts[] = [false, $text];
        }
    }

    /**
     * Reads a quoted LDML literal starting at the opening quote and moves the index to its closing quote.
     * "''" is an apostrophe both inside and outside quotes.
     *
     * @param string[] $chars
     * @param int      $i
     * @return string
     */
    private function readQuoted(array $chars, int &$i): string
    {
        if (($chars[$i + 1] ?? '') === "'") {
            $i++;
            return "'";
        }

        $text = '';
        for ($i++; isset($chars[$i]); $i++) {
            if ($chars[$i] === "'") {
                if (($chars[$i + 1] ?? '') !== "'") {
                    break;
                }
                $i++;
            }
            $text .= $chars[$i];
        }

        return $text;
    }

    /**
     * Throws in strict mode, otherwise drops removed tokens together with the punctuation next to them.
     *
     * @param array<int, array{0: bool, 1: string|null}> $parts
     * @param string[]                                   $errors
     * @param bool                                       $strict
     * @param callable(string): string                   $literal Renders literal text for the target format.
     * @return string
     * @throws InvalidArgumentException
     */
    private function render(array $parts, array $errors, bool $strict, callable $literal): string
    {
        if ($strict && $errors) {
            throw new InvalidArgumentException('Cannot convert date format: ' . implode('; ', $errors) . '.');
        }

        foreach ($parts as $key => [$isToken, $value]) {
            if (! $isToken || $value !== null) {
                continue;
            }

            // "H:i:s" loses ":s" rather than leaving "HH:mm:"; a leading token takes the punctuation after it
            $neighbour     = $key > 0 ? $key - 1 : $key + 1;
            $isPunctuation = isset($parts[$neighbour]) && ! $parts[$neighbour][0]
                && preg_match('/^[\s\p{P}\p{S}]*$/u', $parts[$neighbour][1]);

            if ($isPunctuation) {
                $parts[$neighbour][1] = '';
            }
        }

        $result = '';
        foreach ($parts as [$isToken, $value]) {
            $result .= $isToken ? (string) $value : $literal($value);
        }

        return $result;
    }

    /**
     * Quotes the letters of literal text for LDML, keeping surrounding punctuation outside the quotes.
     *
     * @param string $text
     * @return string
     */
    private function quoteLdml(string $text): string
    {
        if (! preg_match('/^([^a-zA-Z]*)(.*?)([^a-zA-Z]*)$/su', $text, $match) || $match[2] === '') {
            return str_replace("'", "''", $text);
        }

        return str_replace("'", "''", $match[1])
            . "'" . str_replace("'", "''", $match[2]) . "'"
            . str_replace("'", "''", $match[3]);
    }

    /**
     * Escapes letters and backslashes so PHP date() prints them as is.
     *
     * @param string $text
     * @return string
     */
    private function escapePhp(string $text): string
    {
        return (string) preg_replace('/[a-zA-Z\\\\]/', '\\\\$0', $text);
    }
}
