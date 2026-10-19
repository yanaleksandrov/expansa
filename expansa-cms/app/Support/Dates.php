<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use DateTimeInterface;
use Expansa\Facades\I18n;
use IntlDateFormatter;

/**
 * Dates in the formats of the General settings: PHP date formats like "F j, Y", written with the ICU
 * patterns of IntlDateFormatter, so the names of months and days follow the language of the dashboard.
 */
final class Dates
{
    /**
     * ICU pattern parts by the PHP date format letters; the letters without one are left out.
     */
    private const array TOKENS = [
        'd' => 'dd',
        'j' => 'd',
        'D' => 'EEE',
        'l' => 'EEEE',
        'N' => 'e',
        'z' => 'D',
        'W' => 'ww',
        'F' => 'MMMM',
        'm' => 'MM',
        'M' => 'MMM',
        'n' => 'M',
        'o' => 'YYYY',
        'Y' => 'y',
        'y' => 'yy',
        'a' => 'a',
        'A' => 'a',
        'g' => 'h',
        'G' => 'H',
        'h' => 'hh',
        'H' => 'HH',
        'i' => 'mm',
        's' => 'ss',
        'v' => 'SSS',
        'e' => 'VV',
        'T' => 'z',
        'P' => 'xxx',
        'O' => 'xx',
    ];

    /**
     * Formatters by locale and pattern, a table formats hundreds of dates with one.
     *
     * @var array<string, IntlDateFormatter>
     */
    private static array $formatters = [];

    /**
     * Format a date by the date and time formats of the settings.
     *
     * @param DateTimeInterface|int|string|null $time     Date, timestamp or a string strtotime() reads, e.g. a database datetime.
     * @param bool                             $withTime Add the time after the date.
     * @return string Empty for an empty or unreadable value.
     */
    public static function format(DateTimeInterface|int|string|null $time, bool $withTime = true): string
    {
        $timestamp = match (true) {
            $time instanceof DateTimeInterface => $time->getTimestamp(),
            is_int($time)                      => $time,
            $time === null, $time === ''       => false,
            default                            => strtotime($time),
        };
        if ($timestamp === false) {
            return '';
        }

        $format = (string) Option::get('date-format', 'F j, Y') . ($withTime ? ' ' . Option::get('time-format', 'g:i a') : '');
        $locale = I18n::locale();

        $formatter = self::$formatters["$locale|$format"] ??= new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, pattern: self::toPattern($format));

        return (string) $formatter->format($timestamp);
    }

    /**
     * Convert a PHP date format to an ICU pattern: "F j, Y" to "MMMM d, y".
     * Other text is quoted, a backslash escapes a letter as in date().
     *
     * @param string $format
     * @return string
     */
    public static function toPattern(string $format): string
    {
        $pattern = '';
        $literal = '';
        $chars   = mb_str_split($format);

        for ($i = 0, $count = count($chars); $i < $count; $i++) {
            $char = $chars[$i];
            if ($char === '\\' && isset($chars[$i + 1])) {
                $literal .= $chars[++$i];
                continue;
            }

            if (! isset(self::TOKENS[$char])) {
                // a letter without an ICU part, e.g. the "S" suffix of "jS", is left out
                $literal .= ctype_alpha($char) ? '' : $char;
                continue;
            }

            $pattern .= self::quote($literal) . self::TOKENS[$char];
            $literal  = '';
        }

        return trim($pattern . self::quote($literal));
    }

    /**
     * Quote the text between the tokens: the letters in quotes, a quote doubled.
     *
     * @param string $text
     * @return string
     */
    private static function quote(string $text): string
    {
        $text = str_replace("'", "''", $text);

        return preg_match('/\p{L}/u', $text) ? "'$text'" : $text;
    }
}
