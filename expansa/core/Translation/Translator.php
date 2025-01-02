<?php

declare(strict_types=1);

namespace Expansa\Translation;

use Expansa\Hook;
use Expansa\Sanitizer;

/**
 * The I18n class provides methods for handling translations in the system, including
 * regular translations, translations with formatting, and conditional translations.
 * The class also offers methods for returning translations sanitized for use in HTML attributes.
 *
 * As text your can use base markdown syntax. For example links looks like this:
 * I18n::f( 'Go to [documentation page](:pageLink) for resolve issue', 'https://google.com' )
 *
 * Main functionalities:
 * - `t|_t(_attr)`: translates a string and returns/outputs it (optionally sanitizes for HTML attributes).
 * - `f|_f(_attr)`: translates a string with placeholders and returns/outputs it (optionally sanitizes for HTML attributes).
 * - `c|_c(_attr)`: translates a string based on a condition and returns/outputs it (optionally sanitizes for HTML attributes).
 *
 * TODO: Implement text pluralization.
 */
class Translator extends Locale
{
    /**
     * Incoming translations routes list.
     *
     * @var array
     */
    protected static array $routes = [];

    /**
     *
     *
     * @var string
     */
    protected static string $pattern = '';

    /**
     * Translates a given string based on the current locale. The method checks for
     * a corresponding translation in a locale-specific JSON file. If a translation
     * exists, it returns the translated string; otherwise, it returns the original string.
     *
     * The translation files are expected to be named according to the locale and
     * follow a JSON format, where keys are original strings and values are their
     * translations.
     *
     * @param string $string The string to be translated.
     * @return string        The translated string, or the original if no translation is found.
     */
    protected function get(string $string): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $source    = $backtrace[1]['file'] ?? null;

        // add routes storages
        static $override = [];
        static $routes   = [];

        if ($source !== null) {
            if (isset($routes[ $source ]) || isset($override[ $source ])) {
                return self::gettext($string, $routes[ $source ] ?? '', $override[ $source ] ?? '');
            }

            // check that incoming path is part of exist routes
            $segments = array_map(fn($key) => basename(rtrim($key, '/')), array_keys(self::$routes));
            $result   = implode('|', $segments);
            $pattern  = sprintf('/(%s)\/([^\/]+)\/[^\/]+$/', $result);
            if (preg_match($pattern, $source, $matches)) {
                $element   = $matches[1] ?? '';
                $directory = $matches[2] ?? '';
                $filename  = sprintf(self::$pattern, $this->getLocale());

                // try to find main & custom translations
                foreach (self::$routes as $route => $targetRoute) {
                    if (! str_starts_with($source, $route)) {
                        continue;
                    }

                    $targetRoute = rtrim($targetRoute, DIRECTORY_SEPARATOR);
                    $targetDir   = basename($targetRoute);
                    if ($directory) {
                        $targetRoute = str_replace(':dirname', $directory, $targetRoute);
                    }

                    if (in_array($element, [ 'plugins', 'themes' ], true)) {
                        $targetDir = $element . DIRECTORY_SEPARATOR . str_replace(':dirname', $directory, $targetDir);
                    }

                    $override[ $source ] ??= sprintf('%s%s/%s.json', EX_I18N, $targetDir, $this->getLocale());
                    $routes[ $source ]   ??= sprintf('%s/%s.json', $targetRoute, $filename);
                }

                return self::gettext($string, $routes[ $source ] ?? '', $override[ $source ] ?? '');
            }
        }
        return $string;
    }

    /**
     * Find text from file.
     *
     * @param string $string
     * @param string $filepath
     * @param string $overrideFilepath
     * @return string
     */
    private static function gettext(string $string, string $filepath, string $overrideFilepath = ''): string
    {
        foreach ([ $overrideFilepath, $filepath ] as $path) {
            if (file_exists($path)) {
                $translations = json_decode(file_get_contents($path) ?: '', true);
                if (isset($translations[ $string ])) {
                    return $translations[ $string ];
                }
            }
        }
        return $string;
    }

    /**
     * Output translated string.
     *
     * @param string $string
     *
     * @since 2025.1
     */
    public function t(string $string): void
    {
        echo self::_t($string);
    }

    /**
     * Get translated string.
     *
     * @param string $string
     * @return string
     */
    public function _t(string $string): string
    {
        return self::get(Markdown::render(htmlentities($string)));
    }

    /**
     * Translation and use in html attribute.
     *
     * @param string $string
     * @return void
     */
    public function t_attr(string $string): void
    {
        echo self::_t_attr($string);
    }

    /**
     * Translation and use in html attribute.
     *
     * @param string $string
     * @return string
     */
    public function _t_attr(string $string): string
    {
        return Sanitizer::attribute(self::_t($string));
    }

    /**
     * Translate with formatting.
     *
     * @param string $string
     * @param mixed ...$args
     * @return void
     */
    public function f(string $string, mixed ...$args): void
    {
        echo self::_f($string, ...$args);
    }

    /**
     * Translate with formatting. Use instead php placeholders like %s and %d, human-readable strings.
     * The function support converting to lowercase, uppercase & first letter to uppercase.
     * To write the placeholder and the suffix together, use the '\' slash.
     *
     * For example:
     *
     * I18n::_f( 'Hi, :Firstname :Lastname, you have :count\st none closed ":TASKNAME" task', 'yan', 'aleksandrov', 1, 'test' )
     *
     * return 'Hi, Yan Aleksandrov, you have 1st none closed "TEST" task'
     *
     * @param string $string
     * @param mixed ...$args
     * @return string
     */
    public function _f(string $string, mixed ...$args): string
    {
        $string = htmlentities($string);
        $string = preg_replace_callback('/(:{1,2})(\w+)(?:\\\\([^:]+))?|%[sd]/u', function ($matches) use (&$args) {
            if ($matches[0] === '%s' || $matches[0] === '%d') {
                return array_shift($args);
            }

            $placeholder = $matches[2] ?? '';
            $suffix      = $matches[3] ?? '';

            $value = array_shift($args);

            if ($matches[1] === '::') {
                $replacement = match (true) {
                    mb_strtolower($placeholder) === $placeholder => mb_strtolower($value),
                    mb_strtoupper($placeholder) === $placeholder => mb_strtoupper($value),
                    default => mb_convert_case($value, MB_CASE_TITLE, 'UTF-8'),
                };
                return $replacement . $suffix;
            }

            return $value . $suffix;
        }, $string);

        return Markdown::render($string);
    }

    /**
     * Output translation with placeholder & sanitize like html attribute.
     *
     * @param string $string
     * @param mixed ...$args
     * @return void
     */
    public function f_attr(string $string, mixed ...$args): void
    {
        echo Sanitizer::attribute(self::_f($string, ...$args));
    }

    /**
     * Return translation with placeholder & sanitize like html attribute.
     *
     * @param string $string
     * @param mixed ...$args
     * @return string
     */
    public function _f_attr(string $string, mixed ...$args): string
    {
        return Sanitizer::attribute(self::_f($string, ...$args));
    }

    /**
     * Output translated text by condition.
     *
     * @param bool $condition
     * @param string $ifString
     * @param string $elseString
     * @return void
     */
    public function c(bool $condition, string $ifString, string $elseString = ''): void
    {
        echo self::_c($condition, $ifString, $elseString);
    }

    /**
     * Return translated text by condition.
     *
     * @param bool $condition
     * @param string $ifString
     * @param string $elseString
     * @return string
     */
    public function _c(bool $condition, string $ifString, string $elseString = ''): string
    {
        return $condition ? self::_t($ifString) : self::_t($elseString);
    }

    /**
     * Output translated text by condition & sanitize like html attribute.
     *
     * @param bool $condition
     * @param string $ifString
     * @param string $elseString
     * @return void
     */
    public function c_attr(bool $condition, string $ifString, string $elseString = ''): void
    {
        echo self::_c_attr($condition, $ifString, $elseString);
    }

    /**
     * Return translated text by condition & sanitize like html attribute.
     *
     * @param bool $condition
     * @param string $ifString
     * @param string $elseString
     * @return string
     */
    public function _c_attr(bool $condition, string $ifString, string $elseString = ''): string
    {
        return Sanitizer::attribute(self::_c($condition, $ifString, $elseString));
    }

    /**
     * Initial setting up of translation rules.
     *
     * @param array $routes
     * @param string $pattern
     * @return void
     */
    public function configure(array $routes, string $pattern): void
    {
        [ self::$routes, self::$pattern ] = [ $routes, $pattern ];
    }

    /**
     * Output local from HTTP.
     *
     * @param string $default
     * @return string
     */
    public function locale(string $default = 'en-US'): string
    {
        return $this->getLocale($default);
    }

    /**
     * Get language by field.
     *
     * @param string $value
     * @param string $getBy
     * @return array
     */
    public function getLanguage(string $value, string $getBy = 'locale'): array
    {
        $languages = self::getLanguages();

        foreach ($languages as $language) {
            if (isset($language[ $getBy ]) && $language[ $getBy ] === $value) {
                return $language;
            }
        }

        return [];
    }

    /**
     * Get language by field.
     *
     * @return array
     */
    public function getLanguagesOptions(): array
    {
        $options   = [];
        $languages = self::getLanguages();

        foreach ($languages as $language) {
            $key  = $language['locale'] ?? $language['iso_639_1'];
            $name = "{$language['name']} - {$language['native']}";

            $options[ $key ] = [
                'flag'    => $language['country'],
                'content' => $language['name'] === $language['native'] ? $language['name'] : $name,
            ];
        }

        return $options;
    }

    /**
     * Get languages list.
     *
     * @return array
     */
    public function getLanguages(): array
    {
        return Hook::call(
            'i18n_get_languages',
            [
                [
                    'name'      => 'Afrikaans',
                    'native'    => 'Afrikaans',
                    'rtl'       => 0,
                    'iso_639_1' => 'af',
                    'iso_639_2' => 'afr',
                    'locale'    => 'af',
                    'country'   => 'za',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Albanian',
                    'native'    => 'Shqip',
                    'rtl'       => 0,
                    'iso_639_1' => 'sq',
                    'iso_639_2' => 'sqi',
                    'locale'    => 'sq',
                    'country'   => 'al',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Algerian Arabic',
                    'native'    => 'الدارجة الجزايرية',
                    'rtl'       => 1,
                    'iso_639_1' => 'ar',
                    'iso_639_2' => null,
                    'locale'    => 'arq',
                    'country'   => 'dz',
                    'nplurals'  => 6,
                    'plural'    => 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5',
                ],
                [
                    'name'      => 'Amharic',
                    'native'    => 'አማርኛ',
                    'rtl'       => 0,
                    'iso_639_1' => 'am',
                    'iso_639_2' => 'amh',
                    'locale'    => 'am',
                    'country'   => 'et',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Arabic',
                    'native'    => 'العربية',
                    'rtl'       => 1,
                    'iso_639_1' => 'ar',
                    'iso_639_2' => 'ara',
                    'locale'    => 'ar',
                    'country'   => 'ae',
                    'nplurals'  => 6,
                    'plural'    => 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5',
                ],
                [
                    'name'      => 'Armenian',
                    'native'    => 'Հայերեն',
                    'rtl'       => 0,
                    'iso_639_1' => 'hy',
                    'iso_639_2' => 'hye',
                    'locale'    => 'hy',
                    'country'   => 'am',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Assamese',
                    'native'    => 'অসমীয়া',
                    'rtl'       => 0,
                    'iso_639_1' => 'as',
                    'iso_639_2' => 'asm',
                    'locale'    => 'as',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Aymara',
                    'native'    => 'aymar aru',
                    'rtl'       => 0,
                    'iso_639_1' => 'ay',
                    'iso_639_2' => 'aym',
                    'locale'    => 'ay-BO',
                    'country'   => 'bo',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Azerbaijani',
                    'native'    => 'Azərbaycan dili',
                    'rtl'       => 0,
                    'iso_639_1' => 'az',
                    'iso_639_2' => 'aze',
                    'locale'    => 'az',
                    'country'   => 'az',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Balochi Southern',
                    'native'    => 'بلوچی مکرانی',
                    'rtl'       => 1,
                    'iso_639_1' => null,
                    'iso_639_2' => null,
                    'locale'    => 'bcc',
                    'country'   => 'pk',
                    'nplurals'  => 1,
                    'plural'    => 0,
                ],
                [
                    'name'      => 'Basque',
                    'native'    => 'Euskara',
                    'rtl'       => 0,
                    'iso_639_1' => 'eu',
                    'iso_639_2' => 'eus',
                    'locale'    => 'eu',
                    'country'   => 'es',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Belarusian',
                    'native'    => 'Беларуская мова',
                    'rtl'       => 0,
                    'iso_639_1' => 'be',
                    'iso_639_2' => 'bel',
                    'locale'    => 'be-BY',
                    'country'   => 'by',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Bengali',
                    'native'    => 'বাংলা',
                    'rtl'       => 0,
                    'iso_639_1' => 'bn',
                    'iso_639_2' => 'ben',
                    'locale'    => 'bn-BD',
                    'country'   => 'bn',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Bosnian',
                    'native'    => 'Bosanski',
                    'rtl'       => 0,
                    'iso_639_1' => 'bs',
                    'iso_639_2' => 'bos',
                    'locale'    => 'bs-BA',
                    'country'   => 'ba',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Bulgarian',
                    'native'    => 'Български',
                    'rtl'       => 0,
                    'iso_639_1' => 'bg',
                    'iso_639_2' => 'bul',
                    'locale'    => 'bg-BG',
                    'country'   => 'bg',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Catalan',
                    'native'    => 'Català',
                    'rtl'       => 0,
                    'iso_639_1' => 'ca',
                    'iso_639_2' => 'cat',
                    'locale'    => 'ca',
                    'country'   => 'ad',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Catalan (Balear)',
                    'native'    => 'Català (Balear)',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => 'bal',
                    'locale'    => 'bal',
                    'country'   => 'es',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Chinese (China)',
                    'native'    => '简体中文',
                    'rtl'       => 0,
                    'iso_639_1' => 'zh',
                    'iso_639_2' => 'zho',
                    'locale'    => 'zh-CN',
                    'country'   => 'cn',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Chinese (Hong Kong)',
                    'native'    => '香港中文版\t',
                    'rtl'       => 0,
                    'iso_639_1' => 'zh',
                    'iso_639_2' => 'zho',
                    'locale'    => 'zh-HK',
                    'country'   => 'hk',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Chinese (Singapore)',
                    'native'    => '中文',
                    'rtl'       => 0,
                    'iso_639_1' => 'zh',
                    'iso_639_2' => 'zho',
                    'locale'    => 'zh-SG',
                    'country'   => 'sg',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Chinese (Taiwan)',
                    'native'    => '繁體中文',
                    'rtl'       => 0,
                    'iso_639_1' => 'zh',
                    'iso_639_2' => 'zho',
                    'locale'    => 'zh-TW',
                    'country'   => 'tw',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Corsican',
                    'native'    => 'Corsu',
                    'rtl'       => 0,
                    'iso_639_1' => 'co',
                    'iso_639_2' => 'cos',
                    'locale'    => 'co',
                    'country'   => 'it',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Croatian',
                    'native'    => 'Hrvatski',
                    'rtl'       => 0,
                    'iso_639_1' => 'hr',
                    'iso_639_2' => 'hrv',
                    'locale'    => 'hr',
                    'country'   => 'hr',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Czech',
                    'native'    => 'Čeština‎',
                    'rtl'       => 0,
                    'iso_639_1' => 'cs',
                    'iso_639_2' => 'ces',
                    'locale'    => 'cs-CZ',
                    'country'   => 'cz',
                    'nplurals'  => 3,
                    'plural'    => '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2',
                ],
                [
                    'name'      => 'Danish',
                    'native'    => 'Dansk',
                    'rtl'       => 0,
                    'iso_639_1' => 'da',
                    'iso_639_2' => 'dan',
                    'locale'    => 'da-DK',
                    'country'   => 'dk',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Dutch',
                    'native'    => 'Nederlands',
                    'rtl'       => 0,
                    'iso_639_1' => 'nl',
                    'iso_639_2' => 'nld',
                    'locale'    => 'nl-NL',
                    'country'   => 'nl',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Dutch (Belgium)',
                    'native'    => 'Nederlands (België)',
                    'rtl'       => 0,
                    'iso_639_1' => 'nl',
                    'iso_639_2' => 'nld',
                    'locale'    => 'nl-BE',
                    'country'   => 'be',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Dzongkha',
                    'native'    => 'རྫོང་ཁ',
                    'rtl'       => 0,
                    'iso_639_1' => 'dz',
                    'iso_639_2' => 'dzo',
                    'locale'    => 'dzo',
                    'country'   => 'bt',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'English (Australia)',
                    'native'    => 'English (Australia)',
                    'rtl'       => 0,
                    'iso_639_1' => 'en',
                    'iso_639_2' => 'eng',
                    'locale'    => 'en-AU',
                    'country'   => 'au',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'English (Canada)',
                    'native'    => 'English (Canada)',
                    'rtl'       => 0,
                    'iso_639_1' => 'en',
                    'iso_639_2' => 'eng',
                    'locale'    => 'en-CA',
                    'country'   => 'ca',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'English (New Zealand)',
                    'native'    => 'English (New Zealand)',
                    'rtl'       => 0,
                    'iso_639_1' => 'en',
                    'iso_639_2' => 'eng',
                    'locale'    => 'en-NZ',
                    'country'   => 'nz',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'English (South Africa)',
                    'native'    => 'English (South Africa)',
                    'rtl'       => 0,
                    'iso_639_1' => 'en',
                    'iso_639_2' => 'eng',
                    'locale'    => 'en-ZA',
                    'country'   => 'za',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'English (UK)',
                    'native'    => 'English (UK)',
                    'rtl'       => 0,
                    'iso_639_1' => 'en',
                    'iso_639_2' => 'eng',
                    'locale'    => 'en-GB',
                    'country'   => 'gb',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'English (US)',
                    'native'    => 'English (US)',
                    'rtl'       => 0,
                    'iso_639_1' => 'en',
                    'iso_639_2' => 'eng',
                    'locale'    => 'en-US',
                    'country'   => 'us',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Estonian',
                    'native'    => 'Eesti',
                    'rtl'       => 0,
                    'iso_639_1' => 'et',
                    'iso_639_2' => 'est',
                    'locale'    => 'et',
                    'country'   => 'ee',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Faroese',
                    'native'    => 'Føroyskt',
                    'rtl'       => 0,
                    'iso_639_1' => 'fo',
                    'iso_639_2' => 'fao',
                    'locale'    => 'fo',
                    'country'   => 'fo',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Finnish',
                    'native'    => 'Suomi',
                    'rtl'       => 0,
                    'iso_639_1' => 'fi',
                    'iso_639_2' => 'fin',
                    'locale'    => 'fi',
                    'country'   => 'fi',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'French (Belgium)',
                    'native'    => 'Français de Belgique',
                    'rtl'       => 0,
                    'iso_639_1' => 'fr',
                    'iso_639_2' => 'fra',
                    'locale'    => 'fr-BE',
                    'country'   => 'be',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'French (Canada)',
                    'native'    => 'Français du Canada',
                    'rtl'       => 0,
                    'iso_639_1' => 'fr',
                    'iso_639_2' => 'fra',
                    'locale'    => 'fr-CA',
                    'country'   => 'ca',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'French (France)',
                    'native'    => 'Français',
                    'rtl'       => 0,
                    'iso_639_1' => 'fr',
                    'iso_639_2' => 'fra',
                    'locale'    => 'fr-FR',
                    'country'   => 'fr',
                    'nplurals'  => 2,
                    'plural'    => 'n > 1',
                ],
                [
                    'name'      => 'French (Switzerland)',
                    'native'    => 'Français de Suisse',
                    'rtl'       => 0,
                    'iso_639_1' => 'fr',
                    'iso_639_2' => 'fra',
                    'locale'    => 'fr-CH',
                    'country'   => 'ch',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Frisian',
                    'native'    => 'Frysk',
                    'rtl'       => 0,
                    'iso_639_1' => 'fy',
                    'iso_639_2' => 'fry',
                    'locale'    => 'fy',
                    'country'   => 'nl',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Friulian',
                    'native'    => 'Friulian',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => 'fur',
                    'locale'    => 'fur',
                    'country'   => 'it',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Fulah',
                    'native'    => 'Pulaar',
                    'rtl'       => 0,
                    'iso_639_1' => 'ff',
                    'iso_639_2' => 'fuc',
                    'locale'    => 'fuc',
                    'country'   => 'sn',
                    'nplurals'  => 2,
                    'plural'    => 'n!=1',
                ],
                [
                    'name'      => 'Galician',
                    'native'    => 'Galego',
                    'rtl'       => 0,
                    'iso_639_1' => 'gl',
                    'iso_639_2' => 'glg',
                    'locale'    => 'gl-ES',
                    'country'   => 'es',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Georgian',
                    'native'    => 'ქართული',
                    'rtl'       => 0,
                    'iso_639_1' => 'ka',
                    'iso_639_2' => 'kat',
                    'locale'    => 'ka-GE',
                    'country'   => 'ge',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'German',
                    'native'    => 'Deutsch',
                    'rtl'       => 0,
                    'iso_639_1' => 'de',
                    'iso_639_2' => 'deu',
                    'locale'    => 'de-DE',
                    'country'   => 'de',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'German (Switzerland)',
                    'native'    => 'Deutsch (Schweiz)',
                    'rtl'       => 0,
                    'iso_639_1' => 'de',
                    'iso_639_2' => null,
                    'locale'    => 'de-CH',
                    'country'   => 'ch',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Greek',
                    'native'    => 'Ελληνικά',
                    'rtl'       => 0,
                    'iso_639_1' => 'el',
                    'iso_639_2' => 'ell',
                    'locale'    => 'el',
                    'country'   => 'gr',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Greenlandic',
                    'native'    => 'Kalaallisut',
                    'rtl'       => 0,
                    'iso_639_1' => 'kl',
                    'iso_639_2' => 'kal',
                    'locale'    => 'kal',
                    'country'   => 'gl',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Guaraní',
                    'native'    => 'Avañe’ẽ',
                    'rtl'       => 0,
                    'iso_639_1' => 'gn',
                    'iso_639_2' => 'grn',
                    'locale'    => 'gn',
                    'country'   => 'py',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Hausa',
                    'native'    => 'هَوُسَ',
                    'rtl'       => 1,
                    'iso_639_1' => 'ha',
                    'iso_639_2' => 'hau',
                    'locale'    => 'ha-NG',
                    'country'   => 'ng',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Hawaiian',
                    'native'    => 'Ōlelo Hawaiʻi',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => 'haw',
                    'locale'    => 'haw-US',
                    'country'   => 'us',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Hazaragi',
                    'native'    => 'هزاره گی',
                    'rtl'       => 1,
                    'iso_639_1' => null,
                    'iso_639_2' => null,
                    'locale'    => 'haz',
                    'country'   => 'af',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Hebrew',
                    'native'    => 'עִבְרִית',
                    'rtl'       => 1,
                    'iso_639_1' => 'he',
                    'iso_639_2' => 'heb',
                    'locale'    => 'he-IL',
                    'country'   => 'il',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Hindi',
                    'native'    => 'हिन्दी',
                    'rtl'       => 0,
                    'iso_639_1' => 'hi',
                    'iso_639_2' => 'hin',
                    'locale'    => 'hi-IN',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Hungarian',
                    'native'    => 'Magyar',
                    'rtl'       => 0,
                    'iso_639_1' => 'hu',
                    'iso_639_2' => 'hun',
                    'locale'    => 'hu-HU',
                    'country'   => 'hu',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Icelandic',
                    'native'    => 'Íslenska',
                    'rtl'       => 0,
                    'iso_639_1' => 'is',
                    'iso_639_2' => 'isl',
                    'locale'    => 'is-IS',
                    'country'   => 'is',
                    'nplurals'  => 2,
                    'plural'    => '(n % 100 != 1 && n % 100 != 21 && n % 100 != 31 && n % 100 != 41 && n % 100 != 51 && n % 100 != 61 && n % 100 != 71 && n % 100 != 81 && n % 100 != 91)',
                ],
                [
                    'name'      => 'Indonesian',
                    'native'    => 'Bahasa Indonesia',
                    'rtl'       => 0,
                    'iso_639_1' => 'id',
                    'iso_639_2' => 'ind',
                    'locale'    => 'id-ID',
                    'country'   => 'id',
                    'nplurals'  => 2,
                    'plural'    => 'n > 1',
                ],
                [
                    'name'      => 'Irish',
                    'native'    => 'Gaelige',
                    'rtl'       => 0,
                    'iso_639_1' => 'ga',
                    'iso_639_2' => 'gle',
                    'locale'    => 'ga',
                    'country'   => 'ie',
                    'nplurals'  => 5,
                    'plural'    => 'n==1 ? 0 : n==2 ? 1 : n<7 ? 2 : n<11 ? 3 : 4',
                ],
                [
                    'name'      => 'Italian',
                    'native'    => 'Italiano',
                    'rtl'       => 0,
                    'iso_639_1' => 'it',
                    'iso_639_2' => 'ita',
                    'locale'    => 'it-IT',
                    'country'   => 'it',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Japanese',
                    'native'    => '日本語',
                    'rtl'       => 0,
                    'iso_639_1' => 'ja',
                    'iso_639_2' => 'jpn',
                    'locale'    => 'ja',
                    'country'   => 'jp',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Javanese',
                    'native'    => 'Basa Jawa',
                    'rtl'       => 0,
                    'iso_639_1' => 'jv',
                    'iso_639_2' => 'jav',
                    'locale'    => 'jv-ID',
                    'country'   => 'id',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Kabyle',
                    'native'    => 'Taqbaylit',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => 'kab',
                    'locale'    => 'kab',
                    'country'   => 'dz',
                    'nplurals'  => 2,
                    'plural'    => '(n > 1)',
                ],
                [
                    'name'      => 'Kannada',
                    'native'    => 'ಕನ್ನಡ',
                    'rtl'       => 0,
                    'iso_639_1' => 'kn',
                    'iso_639_2' => 'kan',
                    'locale'    => 'kn',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Kazakh',
                    'native'    => 'Қазақ тілі',
                    'rtl'       => 0,
                    'iso_639_1' => 'kk',
                    'iso_639_2' => 'kaz',
                    'locale'    => 'kk',
                    'country'   => 'kz',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Khmer',
                    'native'    => 'ភាសាខ្មែរ',
                    'rtl'       => 0,
                    'iso_639_1' => 'km',
                    'iso_639_2' => 'khm',
                    'locale'    => 'km',
                    'country'   => 'kh',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Kinyarwanda',
                    'native'    => 'Ikinyarwanda',
                    'rtl'       => 0,
                    'iso_639_1' => 'rw',
                    'iso_639_2' => 'kin',
                    'locale'    => 'kin',
                    'country'   => 'rw',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Kirghiz',
                    'native'    => 'кыргыз тили',
                    'rtl'       => 0,
                    'iso_639_1' => 'ky',
                    'iso_639_2' => 'kir',
                    'locale'    => 'ky-KY',
                    'country'   => 'kg',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Klingon',
                    'native'    => 'TlhIngan',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => 'tlh',
                    'locale'    => 'tl-ST',
                    'country'   => 'st',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Korean',
                    'native'    => '한국어',
                    'rtl'       => 0,
                    'iso_639_1' => 'ko',
                    'iso_639_2' => 'kor',
                    'locale'    => 'ko-KR',
                    'country'   => 'kr',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Kurdish (Kurmanji)',
                    'native'    => 'Kurdî',
                    'rtl'       => 0,
                    'iso_639_1' => 'ku',
                    'iso_639_2' => null,
                    'locale'    => 'ku-TR',
                    'country'   => 'tr',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Kurdish (Sorani)',
                    'native'    => 'كوردی‎',
                    'rtl'       => 1,
                    'iso_639_1' => 'ku',
                    'iso_639_2' => null,
                    'locale'    => 'ckb',
                    'country'   => 'iq',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Lao',
                    'native'    => 'ພາສາລາວ',
                    'rtl'       => 0,
                    'iso_639_1' => 'lo',
                    'iso_639_2' => 'lao',
                    'locale'    => 'lo',
                    'country'   => 'la',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Latvian',
                    'native'    => 'Latviešu valoda',
                    'rtl'       => 0,
                    'iso_639_1' => 'lv',
                    'iso_639_2' => 'lav',
                    'locale'    => 'lv',
                    'country'   => 'lv',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)',
                ],
                [
                    'name'      => 'Limburgish',
                    'native'    => 'Limburgs',
                    'rtl'       => 0,
                    'iso_639_1' => 'li',
                    'iso_639_2' => 'lim',
                    'locale'    => 'li',
                    'country'   => 'nl',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Lingala',
                    'native'    => 'Ngala',
                    'rtl'       => 0,
                    'iso_639_1' => 'ln',
                    'iso_639_2' => 'lin',
                    'locale'    => 'lin',
                    'country'   => 'cd',
                    'nplurals'  => 2,
                    'plural'    => 'n>1',
                ],
                [
                    'name'      => 'Lithuanian',
                    'native'    => 'Lietuvių kalba',
                    'rtl'       => 0,
                    'iso_639_1' => 'lt',
                    'iso_639_2' => 'lit',
                    'locale'    => 'lt-LT',
                    'country'   => 'lt',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Luxembourgish',
                    'native'    => 'Lëtzebuergesch',
                    'rtl'       => 0,
                    'iso_639_1' => 'lb',
                    'iso_639_2' => 'ltz',
                    'locale'    => 'lb-LU',
                    'country'   => 'lu',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Macedonian',
                    'native'    => 'Македонски јазик',
                    'rtl'       => 0,
                    'iso_639_1' => 'mk',
                    'iso_639_2' => 'mkd',
                    'locale'    => 'mk-MK',
                    'country'   => 'mk',
                    'nplurals'  => 2,
                    'plural'    => 'n==1 || n%10==1 ? 0 : 1',
                ],
                [
                    'name'      => 'Malagasy',
                    'native'    => 'Malagasy',
                    'rtl'       => 0,
                    'iso_639_1' => 'mg',
                    'iso_639_2' => 'mlg',
                    'locale'    => 'mg-MG',
                    'country'   => 'mg',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Malay',
                    'native'    => 'Bahasa Melayu',
                    'rtl'       => 0,
                    'iso_639_1' => 'ms',
                    'iso_639_2' => 'msa',
                    'locale'    => 'ms-MY',
                    'country'   => 'my',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Malayalam',
                    'native'    => 'മലയാളം',
                    'rtl'       => 0,
                    'iso_639_1' => 'ml',
                    'iso_639_2' => 'mal',
                    'locale'    => 'ml-IN',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Maori',
                    'native'    => 'Te Reo Māori',
                    'rtl'       => 0,
                    'iso_639_1' => 'mi',
                    'iso_639_2' => null,
                    'locale'    => 'mri',
                    'country'   => 'nz',
                    'nplurals'  => 2,
                    'plural'    => '(n > 1)',
                ],
                [
                    'name'      => 'Mongolian',
                    'native'    => 'Монгол',
                    'rtl'       => 0,
                    'iso_639_1' => 'mn',
                    'iso_639_2' => 'mon',
                    'locale'    => 'mn',
                    'country'   => 'mn',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Montenegrin',
                    'native'    => 'Crnogorski jezik',
                    'rtl'       => 0,
                    'iso_639_1' => 'me',
                    'iso_639_2' => null,
                    'locale'    => 'me-ME',
                    'country'   => 'me',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Moroccan Arabic',
                    'native'    => 'العربية المغربية',
                    'rtl'       => 1,
                    'iso_639_1' => 'ar',
                    'iso_639_2' => null,
                    'locale'    => 'ary',
                    'country'   => 'ma',
                    'nplurals'  => 6,
                    'plural'    => 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5',
                ],
                [
                    'name'      => 'Myanmar (Burmese)',
                    'native'    => 'ဗမာစာ',
                    'rtl'       => 0,
                    'iso_639_1' => 'my',
                    'iso_639_2' => 'mya',
                    'locale'    => 'my-MM',
                    'country'   => 'mm',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Nepali',
                    'native'    => 'नेपाली',
                    'rtl'       => 0,
                    'iso_639_1' => 'ne',
                    'iso_639_2' => 'nep',
                    'locale'    => 'ne-NP',
                    'country'   => 'np',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Norwegian (Bokmål)',
                    'native'    => 'Norsk bokmål',
                    'rtl'       => 0,
                    'iso_639_1' => 'nb',
                    'iso_639_2' => 'nob',
                    'locale'    => 'nb-NO',
                    'country'   => 'no',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Oriya',
                    'native'    => 'ଓଡ଼ିଆ',
                    'rtl'       => 0,
                    'iso_639_1' => 'or',
                    'iso_639_2' => 'ory',
                    'locale'    => 'ory',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Pashto',
                    'native'    => 'پښتو',
                    'rtl'       => 1,
                    'iso_639_1' => 'ps',
                    'iso_639_2' => 'pus',
                    'locale'    => 'ps',
                    'country'   => 'af',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Persian',
                    'native'    => 'فارسی',
                    'rtl'       => 1,
                    'iso_639_1' => 'fa',
                    'iso_639_2' => 'fas',
                    'locale'    => 'fa-IR',
                    'country'   => 'ir',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Persian (Afghanistan)',
                    'native'    => '(فارسی (افغانستان',
                    'rtl'       => 1,
                    'iso_639_1' => 'fa',
                    'iso_639_2' => 'fas',
                    'locale'    => 'fa-AF',
                    'country'   => 'af',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Polish',
                    'native'    => 'Polski',
                    'rtl'       => 0,
                    'iso_639_1' => 'pl',
                    'iso_639_2' => 'pol',
                    'locale'    => 'pl-PL',
                    'country'   => 'pl',
                    'nplurals'  => 3,
                    'plural'    => '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Portuguese (Brazil)',
                    'native'    => 'Português do Brasil',
                    'rtl'       => 0,
                    'iso_639_1' => 'pt',
                    'iso_639_2' => 'por',
                    'locale'    => 'pt-BR',
                    'country'   => 'br',
                    'nplurals'  => 2,
                    'plural'    => '(n > 1)',
                ],
                [
                    'name'      => 'Portuguese (Portugal)',
                    'native'    => 'Português',
                    'rtl'       => 0,
                    'iso_639_1' => 'pt',
                    'iso_639_2' => 'por',
                    'locale'    => 'pt-PT',
                    'country'   => 'pt',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Punjabi',
                    'native'    => 'ਪੰਜਾਬੀ',
                    'rtl'       => 0,
                    'iso_639_1' => 'pa',
                    'iso_639_2' => 'pan',
                    'locale'    => 'pa-IN',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Rohingya',
                    'native'    => 'Ruáinga',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => null,
                    'locale'    => 'rhg',
                    'country'   => 'mm',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Romanian',
                    'native'    => 'Română',
                    'rtl'       => 0,
                    'iso_639_1' => 'ro',
                    'iso_639_2' => 'ron',
                    'locale'    => 'ro-RO',
                    'country'   => 'ro',
                    'nplurals'  => 3,
                    'plural'    => '(n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2)',
                ],
                [
                    'name'      => 'Romansh Vallader',
                    'native'    => 'Rumantsch Vallader',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => 'rm',
                    'locale'    => 'roh',
                    'country'   => 'ch',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Russian',
                    'native'    => 'Русский',
                    'rtl'       => 0,
                    'iso_639_1' => 'ru',
                    'iso_639_2' => 'rus',
                    'locale'    => 'ru-RU',
                    'country'   => 'ru',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Sanskrit',
                    'native'    => 'भारतम्',
                    'rtl'       => 0,
                    'iso_639_1' => 'sa',
                    'iso_639_2' => 'san',
                    'locale'    => 'sa-IN',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Sardinian',
                    'native'    => 'Sardu',
                    'rtl'       => 0,
                    'iso_639_1' => 'sc',
                    'iso_639_2' => 'srd',
                    'locale'    => 'srd',
                    'country'   => 'it',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Scottish Gaelic',
                    'native'    => 'Gàidhlig',
                    'rtl'       => 0,
                    'iso_639_1' => 'gd',
                    'iso_639_2' => 'gla',
                    'locale'    => 'gd',
                    'country'   => 'gb',
                    'nplurals'  => 4,
                    'plural'    => '(n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3',
                ],
                [
                    'name'      => 'Serbian',
                    'native'    => 'Српски језик',
                    'rtl'       => 0,
                    'iso_639_1' => 'sr',
                    'iso_639_2' => 'srp',
                    'locale'    => 'sr-RS',
                    'country'   => 'rs',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Silesian',
                    'native'    => 'Ślōnskŏ gŏdka',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => null,
                    'locale'    => 'szl',
                    'country'   => 'pl',
                    'nplurals'  => 3,
                    'plural'    => '(n==1 ? 0 : n%10>=2 && n%10<=4 && n%100==20 ? 1 : 2)',
                ],
                [
                    'name'      => 'Sindhi',
                    'native'    => 'سنڌي',
                    'rtl'       => 1,
                    'iso_639_1' => 'sd',
                    'iso_639_2' => 'sd',
                    'locale'    => 'snd',
                    'country'   => 'pk',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Sinhala',
                    'native'    => 'සිංහල',
                    'rtl'       => 0,
                    'iso_639_1' => 'si',
                    'iso_639_2' => 'sin',
                    'locale'    => 'si-LK',
                    'country'   => 'lk',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Slovak',
                    'native'    => 'Slovenčina',
                    'rtl'       => 0,
                    'iso_639_1' => 'sk',
                    'iso_639_2' => 'slk',
                    'locale'    => 'sk-SK',
                    'country'   => 'sk',
                    'nplurals'  => 3,
                    'plural'    => '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2',
                ],
                [
                    'name'      => 'Slovenian',
                    'native'    => 'Slovenščina',
                    'rtl'       => 0,
                    'iso_639_1' => 'sl',
                    'iso_639_2' => 'slv',
                    'locale'    => 'sl-SI',
                    'country'   => 'si',
                    'nplurals'  => 4,
                    'plural'    => '(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3)',
                ],
                [
                    'name'      => 'Somali',
                    'native'    => 'Afsoomaali',
                    'rtl'       => 0,
                    'iso_639_1' => 'so',
                    'iso_639_2' => 'som',
                    'locale'    => 'so-SO',
                    'country'   => 'so',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Argentina)',
                    'native'    => 'Español de Argentina',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-AR',
                    'country'   => 'ar',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Chile)',
                    'native'    => 'Español de Chile',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-CL',
                    'country'   => 'cl',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Colombia)',
                    'native'    => 'Español de Colombia',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-CO',
                    'country'   => 'co',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Guatemala)',
                    'native'    => 'Español de Guatemala',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-GT',
                    'country'   => 'gt',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Mexico)',
                    'native'    => 'Español de México',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-MX',
                    'country'   => 'mx',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Peru)',
                    'native'    => 'Español de Perú',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-PE',
                    'country'   => 'pe',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Puerto Rico)',
                    'native'    => 'Español de Puerto Rico',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-PR',
                    'country'   => 'pr',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Spain)',
                    'native'    => 'Español',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-ES',
                    'country'   => 'es',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Spanish (Venezuela)',
                    'native'    => 'Español de Venezuela',
                    'rtl'       => 0,
                    'iso_639_1' => 'es',
                    'iso_639_2' => 'spa',
                    'locale'    => 'es-VE',
                    'country'   => 've',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Sundanese',
                    'native'    => 'Basa Sunda',
                    'rtl'       => 0,
                    'iso_639_1' => 'su',
                    'iso_639_2' => 'sun',
                    'locale'    => 'su-ID',
                    'country'   => 'id',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Swahili',
                    'native'    => 'Kiswahili',
                    'rtl'       => 0,
                    'iso_639_1' => 'sw',
                    'iso_639_2' => 'swa',
                    'locale'    => 'sw',
                    'country'   => 'ug',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Swedish',
                    'native'    => 'Svenska',
                    'rtl'       => 0,
                    'iso_639_1' => 'sv',
                    'iso_639_2' => 'swe',
                    'locale'    => 'sv-SE',
                    'country'   => 'se',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Tagalog',
                    'native'    => 'Tagalog',
                    'rtl'       => 0,
                    'iso_639_1' => 'tl',
                    'iso_639_2' => 'tgl',
                    'locale'    => 'tl',
                    'country'   => 'ph',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Tajik',
                    'native'    => 'Тоҷикӣ',
                    'rtl'       => 0,
                    'iso_639_1' => 'tg',
                    'iso_639_2' => 'tgk',
                    'locale'    => 'tg',
                    'country'   => 'tj',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Tamazight (Central Atlas)',
                    'native'    => 'ⵜⴰⵎⴰⵣⵉⵖⵜ',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => 'tzm',
                    'locale'    => 'tzm',
                    'country'   => 'ma',
                    'nplurals'  => 2,
                    'plural'    => '(n > 1)',
                ],
                [
                    'name'      => 'Tamil',
                    'native'    => 'தமிழ்',
                    'rtl'       => 0,
                    'iso_639_1' => 'ta',
                    'iso_639_2' => 'tam',
                    'locale'    => 'ta-IN',
                    'country'   => 'in',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Tamil (Sri Lanka)',
                    'native'    => 'தமிழ்',
                    'rtl'       => 0,
                    'iso_639_1' => 'ta',
                    'iso_639_2' => 'tam',
                    'locale'    => 'ta-LK',
                    'country'   => 'lk',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Tatar',
                    'native'    => 'Татар теле',
                    'rtl'       => 0,
                    'iso_639_1' => 'tt',
                    'iso_639_2' => 'tat',
                    'locale'    => 'tt-RU',
                    'country'   => 'ru',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Thai',
                    'native'    => 'ไทย',
                    'rtl'       => 0,
                    'iso_639_1' => 'th',
                    'iso_639_2' => 'tha',
                    'locale'    => 'th',
                    'country'   => 'th',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Tigrinya',
                    'native'    => 'ትግርኛ',
                    'rtl'       => 0,
                    'iso_639_1' => 'ti',
                    'iso_639_2' => 'tir',
                    'locale'    => 'tir',
                    'country'   => 'er',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Turkish',
                    'native'    => 'Türkçe',
                    'rtl'       => 0,
                    'iso_639_1' => 'tr',
                    'iso_639_2' => 'tur',
                    'locale'    => 'tr-TR',
                    'country'   => 'tr',
                    'nplurals'  => 2,
                    'plural'    => '(n > 1)',
                ],
                [
                    'name'      => 'Turkmen',
                    'native'    => 'Türkmençe',
                    'rtl'       => 0,
                    'iso_639_1' => 'tk',
                    'iso_639_2' => 'tuk',
                    'locale'    => 'tuk',
                    'country'   => 'tm',
                    'nplurals'  => 2,
                    'plural'    => '(n > 1)',
                ],
                [
                    'name'      => 'Tweants',
                    'native'    => 'Twents',
                    'rtl'       => 0,
                    'iso_639_1' => null,
                    'iso_639_2' => null,
                    'locale'    => 'twd',
                    'country'   => 'nl',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Uighur',
                    'native'    => 'Uyƣurqə',
                    'rtl'       => 1,
                    'iso_639_1' => 'ug',
                    'iso_639_2' => 'uig',
                    'locale'    => 'ug-CN',
                    'country'   => 'cn',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Ukrainian',
                    'native'    => 'Українська',
                    'rtl'       => 0,
                    'iso_639_1' => 'uk',
                    'iso_639_2' => 'ukr',
                    'locale'    => 'uk',
                    'country'   => 'ua',
                    'nplurals'  => 3,
                    'plural'    => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
                ],
                [
                    'name'      => 'Urdu',
                    'native'    => 'اردو',
                    'rtl'       => 1,
                    'iso_639_1' => 'ur',
                    'iso_639_2' => 'urd',
                    'locale'    => 'ur',
                    'country'   => 'pk',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Uzbek',
                    'native'    => 'O‘zbekcha',
                    'rtl'       => 0,
                    'iso_639_1' => 'uz',
                    'iso_639_2' => 'uzb',
                    'locale'    => 'uz-UZ',
                    'country'   => 'uz',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Vietnamese',
                    'native'    => 'Tiếng Việt',
                    'rtl'       => 0,
                    'iso_639_1' => 'vi',
                    'iso_639_2' => 'vie',
                    'locale'    => 'vi',
                    'country'   => 'vn',
                    'nplurals'  => 1,
                    'plural'    => '0',
                ],
                [
                    'name'      => 'Walloon',
                    'native'    => 'Walon',
                    'rtl'       => 0,
                    'iso_639_1' => 'wa',
                    'iso_639_2' => 'wln',
                    'locale'    => 'wa',
                    'country'   => 'be',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
                [
                    'name'      => 'Welsh',
                    'native'    => 'Cymraeg',
                    'rtl'       => 0,
                    'iso_639_1' => 'cy',
                    'iso_639_2' => 'cym',
                    'locale'    => 'cy',
                    'country'   => 'gb',
                    'nplurals'  => 4,
                    'plural'    => '(n==1) ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3',
                ],
                [
                    'name'      => 'Yoruba',
                    'native'    => 'Yorùbá',
                    'rtl'       => 0,
                    'iso_639_1' => 'yo',
                    'iso_639_2' => 'yor',
                    'locale'    => 'yor',
                    'country'   => 'ng',
                    'nplurals'  => 2,
                    'plural'    => 'n != 1',
                ],
            ]
        );
    }
}
