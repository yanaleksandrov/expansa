<?php

declare(strict_types=1);

namespace Expansa\Translation;

use Expansa\Hook;
use Expansa\Safe;

/**
 * The I18n class provides methods for handling translations in the system, including
 * regular translations, translations with formatting, and conditional translations.
 * The class also offers methods for returning translations sanitized for use in HTML attributes.
 *
 * As text your can use base markdown syntax. For example links looks like this:
 * t( 'Go to [documentation page](:pageLink) for resolve issue', 'https://google.com' )
 *
 * Main functionalities:
 * - `t|_t(_attr)`: translates a string with placeholders and returns/outputs it (sanitizes for HTML attributes).
 * - `c|_c(_attr)`: translates a string based on a condition and returns/outputs it (sanitizes for HTML attributes).
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
     * Translate with formatting.
     *
     * @param string $string
     * @param mixed ...$args
     * @return void
     */
    public function t(string $string, mixed ...$args): void
    {
        echo self::_t($string, ...$args);
    }

    /**
     * Translate with formatting. Use instead php placeholders like %s and %d, human-readable strings.
     * The function support converting to lowercase, uppercase & first letter to uppercase.
     * To write the placeholder and the suffix together, use the '\' slash.
     *
     * For example:
     *
     * t( 'Hi, :Firstname, you have :count\st none closed ":TASKNAME" task', 'john', 1, 'test' );
     * return 'Hi, John, you have 1st none closed "TEST" task';
     *
     * For security purposes, you can't use html, but can use base markdown layout: bold, italic, headers, image & link:
     *
     * t( '##Hi, *my name* is John, [view my profile](:profileLink).', 'http://example.com');
     * return '<h2>Hi, <em>my name</em> is John, <a href="http://example.com">view my profile</a></h2>';
     *
     * @param string $string
     * @param mixed ...$args
     * @return string
     */
    public function _t(string $string, mixed ...$args): string
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
    public function t_attr(string $string, mixed ...$args): void
    {
        echo Safe::attribute(self::_t($string, ...$args));
    }

    /**
     * Return translation with placeholder & sanitize like html attribute.
     *
     * @param string $string
     * @param mixed ...$args
     * @return string
     */
    public function _t_attr(string $string, mixed ...$args): string
    {
        return Safe::attribute(self::_t($string, ...$args));
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
        return Safe::attribute(self::_c($condition, $ifString, $elseString));
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
        return Hook::call('i18n_get_languages', [
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
        ]);
    }
}
