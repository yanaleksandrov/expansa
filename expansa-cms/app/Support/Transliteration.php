<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;

/**
 * Transliteration of slugs into Latin letters by the tables of the Permalinks settings: slugs either
 * keep their letters or have the letters of all scripts in SCRIPTS replaced, each letter by its own
 * Latin value. The own rules of the site, any text and its replacement, go first in both modes.
 * The option `permalinks` keeps the mode, only the values that differ from SCRIPTS and the own rules.
 *
 * Alphabets replace letter by letter. Hangul syllables are split into the initial, the vowel
 * and the final, each with its own table.
 */
final class Transliteration
{
    /**
     * Scripts with the default Latin values of their letters by groups. Hangul groups follow
     * the order of the jamo in the syllable formula of Unicode.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    public const array SCRIPTS = [
        'latin'    => [
            'letters' => [
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'ā' => 'a',
                'ă' => 'a',
                'ą' => 'a',
                'ǎ' => 'a',
                'æ' => 'ae',
                'ç' => 'c',
                'ć' => 'c',
                'ĉ' => 'c',
                'ċ' => 'c',
                'č' => 'c',
                'ď' => 'd',
                'đ' => 'd',
                'ð' => 'd',
                'è' => 'e',
                'é' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'ē' => 'e',
                'ĕ' => 'e',
                'ė' => 'e',
                'ę' => 'e',
                'ě' => 'e',
                'ĝ' => 'g',
                'ğ' => 'g',
                'ġ' => 'g',
                'ģ' => 'g',
                'ĥ' => 'h',
                'ħ' => 'h',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ĩ' => 'i',
                'ī' => 'i',
                'ĭ' => 'i',
                'į' => 'i',
                'ı' => 'i',
                'ǐ' => 'i',
                'ĳ' => 'ij',
                'ĵ' => 'j',
                'ķ' => 'k',
                'ĺ' => 'l',
                'ļ' => 'l',
                'ľ' => 'l',
                'ŀ' => 'l',
                'ł' => 'l',
                'ñ' => 'n',
                'ń' => 'n',
                'ņ' => 'n',
                'ň' => 'n',
                'ŋ' => 'n',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'ō' => 'o',
                'ŏ' => 'o',
                'ő' => 'o',
                'ơ' => 'o',
                'ǒ' => 'o',
                'œ' => 'oe',
                'ŕ' => 'r',
                'ŗ' => 'r',
                'ř' => 'r',
                'ś' => 's',
                'ŝ' => 's',
                'ş' => 's',
                'š' => 's',
                'ș' => 's',
                'ß' => 'ss',
                'ţ' => 't',
                'ť' => 't',
                'ŧ' => 't',
                'ț' => 't',
                'þ' => 'th',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ũ' => 'u',
                'ū' => 'u',
                'ŭ' => 'u',
                'ů' => 'u',
                'ű' => 'u',
                'ų' => 'u',
                'ư' => 'u',
                'ǔ' => 'u',
                'ŵ' => 'w',
                'ý' => 'y',
                'ÿ' => 'y',
                'ŷ' => 'y',
                'ź' => 'z',
                'ż' => 'z',
                'ž' => 'z',
            ],
        ],
        'cyrillic' => [
            'letters'  => [
                'а' => 'a',
                'б' => 'b',
                'в' => 'v',
                'г' => 'g',
                'д' => 'd',
                'е' => 'e',
                'ё' => 'yo',
                'ж' => 'zh',
                'з' => 'z',
                'и' => 'i',
                'й' => 'y',
                'к' => 'k',
                'л' => 'l',
                'м' => 'm',
                'н' => 'n',
                'о' => 'o',
                'п' => 'p',
                'р' => 'r',
                'с' => 's',
                'т' => 't',
                'у' => 'u',
                'ф' => 'f',
                'х' => 'h',
                'ц' => 'ts',
                'ч' => 'ch',
                'ш' => 'sh',
                'щ' => 'shch',
                'ъ' => '',
                'ы' => 'y',
                'ь' => '',
                'э' => 'e',
                'ю' => 'yu',
                'я' => 'ya',
            ],
            'extended' => [
                'ґ' => 'g',
                'є' => 'ye',
                'і' => 'i',
                'ї' => 'yi',
                'ў' => 'w',
                'ђ' => 'dj',
                'ј' => 'j',
                'љ' => 'lj',
                'њ' => 'nj',
                'ћ' => 'c',
                'џ' => 'dz',
                'ѓ' => 'gj',
                'ќ' => 'kj',
                'ѕ' => 'dz',
                'ә' => 'a',
                'ғ' => 'gh',
                'қ' => 'q',
                'ң' => 'ng',
                'ө' => 'o',
                'ұ' => 'u',
                'ү' => 'u',
                'һ' => 'h',
                'ҳ' => 'h',
                'ҷ' => 'j',
                'ӣ' => 'i',
                'ӯ' => 'u',
                'җ' => 'zh',
                'ҙ' => 'z',
                'ҡ' => 'q',
                'ҫ' => 's',
                'ӑ' => 'a',
                'ӗ' => 'e',
                'ӳ' => 'u',
                'ҝ' => 'g',
                'ҹ' => 'j',
                'ӧ' => 'o',
                'ӱ' => 'u',
                'ӓ' => 'a',
                'ӈ' => 'ng',
                'ӄ' => 'q',
                'ҕ' => 'gh',
                'ӏ' => '',
                'ѣ' => 'e',
                'ѳ' => 'f',
                'ѵ' => 'i',
            ],
        ],
        'arabic'   => [
            'letters'  => [
                'ا' => 'a',
                'أ' => 'a',
                'إ' => 'i',
                'آ' => 'aa',
                'ٱ' => 'a',
                'ء' => '',
                'ؤ' => 'u',
                'ئ' => 'i',
                'ب' => 'b',
                'ت' => 't',
                'ث' => 'th',
                'ج' => 'j',
                'ح' => 'h',
                'خ' => 'kh',
                'د' => 'd',
                'ذ' => 'dh',
                'ر' => 'r',
                'ز' => 'z',
                'س' => 's',
                'ش' => 'sh',
                'ص' => 's',
                'ض' => 'd',
                'ط' => 't',
                'ظ' => 'z',
                'ع' => 'a',
                'غ' => 'gh',
                'ف' => 'f',
                'ق' => 'q',
                'ك' => 'k',
                'ل' => 'l',
                'م' => 'm',
                'ن' => 'n',
                'ه' => 'h',
                'و' => 'w',
                'ي' => 'y',
                'ى' => 'a',
                'ة' => 'a',
            ],
            'extended' => [
                'پ' => 'p',
                'چ' => 'ch',
                'ژ' => 'zh',
                'گ' => 'g',
                'ک' => 'k',
                'ی' => 'y',
                'ٹ' => 't',
                'ڈ' => 'd',
                'ڑ' => 'r',
                'ں' => 'n',
                'ھ' => 'h',
                'ے' => 'e',
            ],
            'marks'    => [
                'َ' => 'a',
                'ِ' => 'i',
                'ُ' => 'u',
                'ً' => 'an',
                'ٍ' => 'in',
                'ٌ' => 'un',
                'ْ' => '',
                'ّ' => '',
                'ـ' => '',
            ],
            'digits'   => [
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
            ],
        ],
        'hebrew'   => [
            'letters'  => [
                'א' => 'a',
                'ב' => 'b',
                'ג' => 'g',
                'ד' => 'd',
                'ה' => 'h',
                'ו' => 'v',
                'ז' => 'z',
                'ח' => 'h',
                'ט' => 't',
                'י' => 'y',
                'כ' => 'k',
                'ל' => 'l',
                'מ' => 'm',
                'נ' => 'n',
                'ס' => 's',
                'ע' => 'a',
                'פ' => 'p',
                'צ' => 'ts',
                'ק' => 'k',
                'ר' => 'r',
                'ש' => 'sh',
                'ת' => 't',
            ],
            'finals'   => [
                'ך' => 'kh',
                'ם' => 'm',
                'ן' => 'n',
                'ף' => 'f',
                'ץ' => 'ts',
            ],
            'extended' => [
                'װ' => 'v',
                'ױ' => 'oy',
                'ײ' => 'ey',
            ],
            'marks'    => [
                'ְ' => '',
                'ֱ' => 'e',
                'ֲ' => 'a',
                'ֳ' => 'o',
                'ִ' => 'i',
                'ֵ' => 'e',
                'ֶ' => 'e',
                'ַ' => 'a',
                'ָ' => 'a',
                'ֹ' => 'o',
                'ֺ' => 'o',
                'ֻ' => 'u',
                'ּ' => '',
                'ֽ' => '',
                'ׁ' => '',
                'ׂ' => '',
                'ׇ' => 'o',
            ],
        ],
        'hangul'   => [
            'initials' => [
                'ㄱ' => 'g',
                'ㄲ' => 'kk',
                'ㄴ' => 'n',
                'ㄷ' => 'd',
                'ㄸ' => 'tt',
                'ㄹ' => 'r',
                'ㅁ' => 'm',
                'ㅂ' => 'b',
                'ㅃ' => 'pp',
                'ㅅ' => 's',
                'ㅆ' => 'ss',
                'ㅇ' => '',
                'ㅈ' => 'j',
                'ㅉ' => 'jj',
                'ㅊ' => 'ch',
                'ㅋ' => 'k',
                'ㅌ' => 't',
                'ㅍ' => 'p',
                'ㅎ' => 'h',
            ],
            'vowels'   => [
                'ㅏ' => 'a',
                'ㅐ' => 'ae',
                'ㅑ' => 'ya',
                'ㅒ' => 'yae',
                'ㅓ' => 'eo',
                'ㅔ' => 'e',
                'ㅕ' => 'yeo',
                'ㅖ' => 'ye',
                'ㅗ' => 'o',
                'ㅘ' => 'wa',
                'ㅙ' => 'wae',
                'ㅚ' => 'oe',
                'ㅛ' => 'yo',
                'ㅜ' => 'u',
                'ㅝ' => 'wo',
                'ㅞ' => 'we',
                'ㅟ' => 'wi',
                'ㅠ' => 'yu',
                'ㅡ' => 'eu',
                'ㅢ' => 'ui',
                'ㅣ' => 'i',
            ],
            'finals'   => [
                'ㄱ' => 'k',
                'ㄲ' => 'k',
                'ㄳ' => 'k',
                'ㄴ' => 'n',
                'ㄵ' => 'n',
                'ㄶ' => 'n',
                'ㄷ' => 't',
                'ㄹ' => 'l',
                'ㄺ' => 'k',
                'ㄻ' => 'm',
                'ㄼ' => 'l',
                'ㄽ' => 'l',
                'ㄾ' => 'l',
                'ㄿ' => 'p',
                'ㅀ' => 'l',
                'ㅁ' => 'm',
                'ㅂ' => 'p',
                'ㅄ' => 'p',
                'ㅅ' => 't',
                'ㅆ' => 't',
                'ㅇ' => 'ng',
                'ㅈ' => 't',
                'ㅊ' => 't',
                'ㅋ' => 'k',
                'ㅌ' => 't',
                'ㅍ' => 'p',
                'ㅎ' => 't',
            ],
        ],
    ];

    /**
     * Rules read once per request; false when slugs keep their letters and there are no own rules.
     *
     * @var null|false|array<string, array<int|string, string>>
     */
    private static null|false|array $rules = null;

    /**
     * Replace the own rules of the site, then the letters of all scripts in a lowercased slug.
     *
     * @param string $slug
     * @return string
     */
    public static function apply(string $slug): string
    {
        $rules = self::rules();
        if ($rules === null) {
            return $slug;
        }

        if ($rules['custom']) {
            $slug = strtr($slug, $rules['custom']);
        }

        if (! $rules['map'] || ! preg_match('/[^\x00-\x7F]/', $slug)) {
            return $slug;
        }

        return strtr(self::syllables($slug, $rules), $rules['map']);
    }

    /**
     * Rules for apply(): the own rules of the site in `custom`, the letters of all
     * scripts in `map`, empty when slugs keep their letters; with them the Latin values of Hangul
     * `initials`, `vowels` and `finals` by their index in a syllable, the first final empty.
     *
     * @return array<string, array<int|string, string>>|null Null when there is nothing to replace.
     */
    private static function rules(): ?array
    {
        if (self::$rules === null) {
            $custom = (array) Option::get('permalinks.rules', []);
            if (! Option::get('permalinks.transliterate', false)) {
                self::$rules = $custom ? ['custom' => $custom, 'map' => []] : false;

                return self::$rules ?: null;
            }

            $map = [];
            foreach (array_keys(self::SCRIPTS) as $script) {
                if ($script !== 'hangul') {
                    $map = [...$map, ...array_merge(...array_values(self::letters($script)))];
                }
            }

            // a standalone jamo is read as an initial, as a vowel, then as a final
            $hangul      = self::letters('hangul');
            self::$rules = [
                'custom'   => $custom,
                'map'      => [...$map, ...$hangul['finals'], ...$hangul['vowels'], ...$hangul['initials']],
                'initials' => array_values($hangul['initials']),
                'vowels'   => array_values($hangul['vowels']),
                'finals'   => ['', ...array_values($hangul['finals'])],
            ];
        }

        return self::$rules ?: null;
    }

    /**
     * Get the letters of a script by groups with their Latin values: the saved ones over the defaults.
     *
     * @param string $script Key of SCRIPTS.
     * @return array<string, array<string, string>>
     */
    public static function letters(string $script): array
    {
        $groups = self::SCRIPTS[$script] ?? [];
        $saved  = (array) Option::get("permalinks.scripts.$script", []);
        foreach ($groups as $group => $letters) {
            $groups[$group] = array_replace($letters, array_intersect_key((array) ($saved[$group] ?? []), $letters));
        }

        return $groups;
    }

    /**
     * Bring the Permalinks settings to the saved form: the mode as a switch, the values in lowercase
     * Latin letters and digits, only those that differ from SCRIPTS; the letters the form did not send
     * keep their saved values. Own rules become `text => replacement`, the text lowercased,
     * the replacement may also have hyphens; a rule without text is dropped.
     *
     * @param array<string, mixed> $permalinks Form values: `mode`, `scripts[script][group][letter]`,
     *                                         `rules[from][]` and `rules[to][]`.
     * @return array<string, mixed>
     */
    public static function normalize(array $permalinks): array
    {
        $typed   = (array) ($permalinks['scripts'] ?? []);
        $scripts = [];
        foreach (self::SCRIPTS as $script => $groups) {
            $letters = self::letters($script);
            foreach ($groups as $group => $defaults) {
                foreach ($defaults as $letter => $latin) {
                    $value = $typed[$script][$group][$letter] ?? $letters[$group][$letter];
                    $value = (string) preg_replace('/[^a-z0-9]+/', '', strtolower((string) $value));
                    if ($value !== $latin) {
                        $scripts[$script][$group][$letter] = $value;
                    }
                }
            }
        }

        $rules = [];
        $to    = (array) ($permalinks['rules']['to'] ?? []);
        foreach ((array) ($permalinks['rules']['from'] ?? []) as $i => $from) {
            $from = mb_strtolower(trim((string) $from), 'UTF-8');
            if (class_exists('Normalizer')) {
                $from = \Normalizer::normalize($from, \Normalizer::FORM_C) ?: $from;
            }

            if ($from !== '') {
                $rules[$from] = (string) preg_replace('/[^a-z0-9-]+/', '', strtolower((string) ($to[$i] ?? '')));
            }
        }

        self::$rules = null;

        return [
            'transliterate' => ($permalinks['mode'] ?? '') === 'latin',
            'scripts'       => $scripts,
            'rules'         => $rules,
        ];
    }

    /**
     * Replace Hangul syllables with the Latin values of their initial, vowel and final.
     *
     * @param string               $slug
     * @param array<string, mixed> $rules
     * @return string
     */
    private static function syllables(string $slug, array $rules): string
    {
        return (string) preg_replace_callback('/[\x{AC00}-\x{D7A3}]/u', static function (array $match) use ($rules): string {
            $index = mb_ord($match[0]) - 0xAC00;

            return $rules['initials'][intdiv($index, 588)]
                . $rules['vowels'][intdiv($index % 588, 28)]
                . $rules['finals'][$index % 28];
        }, $slug);
    }
}
