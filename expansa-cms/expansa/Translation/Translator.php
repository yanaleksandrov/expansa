<?php

declare(strict_types=1);

namespace Expansa\Translation;

use Closure;
use Expansa\Translation\Internal\Markdown;

/**
 * Translations with placeholders and basic Markdown, the locale of the request and the language list.
 * The I18n facade instance; t() and t_attr() are its shortcuts for templates.
 *
 * ```php
 * t('See the [documentation](:pageLink) to resolve this issue', 'https://example.com');
 * ```
 *
 * TODO: Implement text pluralization.
 *
 * @package Expansa\Translation
 */
final class Translator
{
    /**
     * Source directories and the translation directories they map to.
     *
     * @var array<string, string>
     */
    private array $routes = [];

    /**
     * sprintf() pattern of a translation file name, gets the locale.
     */
    private string $pattern = '';

    /**
     * Directory with translation overrides, e.g. edited in the dashboard.
     */
    private string $overrides = '';

    /**
     * Filter of the language list: gets the built-in languages, returns the full list.
     */
    private ?Closure $languages = null;

    /**
     * Locale from the Accept-Language header, false if it can not be detected.
     */
    private string|false|null $httpLocale = null;

    /**
     * Set the translation lookup and the language list filter, replacing the previous configuration.
     *
     * @param array<string, string> $routes    Source directories and their translation directories.
     * @param string                $pattern   sprintf() pattern of a translation file name, gets the locale.
     * @param string                $overrides Directory with translation overrides.
     * @param Closure|null          $languages `fn (array $languages): array`, filters the language list.
     * @return void
     */
    public function configure(array $routes, string $pattern, string $overrides = '', ?Closure $languages = null): void
    {
        $this->routes    = $routes;
        $this->pattern   = $pattern;
        $this->overrides = $overrides;
        $this->languages = $languages;
    }

    /**
     * Translate a string and fill its placeholders, then render its Markdown.
     *
     * - `:name` — the next value, HTML-escaped;
     * - `::name` — the same in the case of the placeholder: `::name` lower, `::NAME` upper, `::Name` title;
     * - `:name\suffix` — the value followed by a suffix: `:count\st` → `1st`;
     * - `%s`, `%d` — the next value as is, for markup around the text like `<a href="...">` and `</a>`.
     *
     * Placeholders without a value stay unchanged. HTML of the string itself is escaped;
     * Markdown gives bold, italic, headers, quotes, images and links.
     *
     * ```php
     * t('Hi, ::Firstname, you have :count\st none closed "::TASKNAME" task.', 'john', 1, 'test');
     * // 'Hi, John, you have 1st none closed "TEST" task.'
     * ```
     *
     * @param string $string
     * @param mixed  ...$args Values of the placeholders, in order of appearance.
     * @return string
     */
    public function translate(string $string, mixed ...$args): string
    {
        $string = htmlentities($string);

        if ($args) {
            $string = preg_replace_callback(
                '{
                    (:{1,2})           # (1) one or two colons
                    (\w+)              # (2) placeholder name
                    (?:\\\\([^:]+))?   # (3) optional suffix after a backslash
                    |
                    %[sd]
                }ux',
                function (array $matches) use (&$args): string {
                    if ($matches[0] === '%s' || $matches[0] === '%d') {
                        return (string) array_shift($args);
                    }

                    $placeholder = $matches[2] ?? '';
                    $value       = (string) array_shift($args);

                    if ($matches[1] === '::') {
                        $value = match (true) {
                            mb_strtolower($placeholder) === $placeholder => mb_strtolower($value),
                            mb_strtoupper($placeholder) === $placeholder => mb_strtoupper($value),
                            default                                      => mb_convert_case($value, MB_CASE_TITLE, 'UTF-8'),
                        };
                    }

                    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ($matches[3] ?? '');
                },
                $string
            );
        }

        return Markdown::render($string);
    }

    /**
     * Translate a string for an HTML attribute value: plain text, escaped once.
     *
     * @param string $string
     * @param mixed  ...$args
     * @return string
     */
    public function translateAttribute(string $string, mixed ...$args): string
    {
        // translate() already escapes, so decode once to avoid "&amp;amp;"
        return trim(htmlspecialchars(html_entity_decode($this->translate($string, ...$args), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES));
    }

    /**
     * Translate one of two strings by a condition.
     *
     * @param bool   $condition
     * @param string $ifString   Translated when the condition is true.
     * @param string $elseString Translated otherwise.
     * @return string
     */
    public function translateIf(bool $condition, string $ifString, string $elseString = ''): string
    {
        return $this->translate($condition ? $ifString : $elseString);
    }

    /**
     * Translate one of two strings by a condition, for an HTML attribute value.
     *
     * @param bool   $condition
     * @param string $ifString
     * @param string $elseString
     * @return string
     */
    public function translateAttributeIf(bool $condition, string $ifString, string $elseString = ''): string
    {
        return $this->translateAttribute($condition ? $ifString : $elseString);
    }

    /**
     * Get the locale of the request from the Accept-Language header: `en-US`.
     *
     * @param string $default Locale when the header is missing or can not be parsed.
     * @return string
     */
    public function locale(string $default = 'en-US'): string
    {
        $this->httpLocale ??= function_exists('locale_accept_from_http')
            ? locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? $default)
            : false;

        return str_replace('_', '-', $this->httpLocale ?: $default);
    }

    /**
     * Find a language by a field value.
     *
     * @param string $value
     * @param string $getBy Field of the language: `locale`, `iso_639_1`, `country`...
     * @return array Empty array if nothing is found.
     */
    public function language(string $value, string $getBy = 'locale'): array
    {
        foreach ($this->languages() as $language) {
            if (($language[$getBy] ?? null) === $value) {
                return $language;
            }
        }

        return [];
    }

    /**
     * Get the options of a language select: flag and name by locale.
     *
     * @return array<string, array{flag: string, content: string}>
     */
    public function languageOptions(): array
    {
        $options = [];
        foreach ($this->languages() as $language) {
            $key  = $language['locale'] ?? $language['iso_639_1'];
            $name = $language['name'] === $language['native'] ? $language['name'] : "{$language['name']} - {$language['native']}";

            $options[$key] = [
                'flag'    => $language['country'],
                'content' => $name,
            ];
        }

        return $options;
    }

    /**
     * Get the language list: the built-in languages passed through the configured filter.
     *
     * @return array[]
     */
    public function languages(): array
    {
        $languages = [
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
        ];

        return $this->languages === null ? $languages : ($this->languages)($languages);
    }

    /**
     * Translate a string from the translation file of the calling file's extension.
     * Not wired into translate() yet: the caller frame points to the facade, not to the template.
     *
     * @param string $string
     * @return string The translation, or the string itself if there is none.
     */
    protected function get(string $string): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $source    = $backtrace[1]['file'] ?? null;

        static $override = [];
        static $routes   = [];

        if ($source === null) {
            return $string;
        }

        if (isset($routes[$source]) || isset($override[$source])) {
            return self::lookup($string, $routes[$source] ?? '', $override[$source] ?? '');
        }

        // the file must be inside one of the routes
        $segments = array_map(fn ($key) => basename(rtrim($key, '/')), array_keys($this->routes));
        $pattern  = sprintf('/(%s)\/([^\/]+)\/[^\/]+$/', implode('|', $segments));
        if (! preg_match($pattern, $source, $matches)) {
            return $string;
        }

        $element   = $matches[1] ?? '';
        $directory = $matches[2] ?? '';
        $filename  = sprintf($this->pattern, $this->locale());

        foreach ($this->routes as $route => $targetRoute) {
            if (! str_starts_with($source, $route)) {
                continue;
            }

            $targetRoute = rtrim($targetRoute, DIRECTORY_SEPARATOR);
            $targetDir   = basename($targetRoute);
            if ($directory) {
                $targetRoute = str_replace(':dirname', $directory, $targetRoute);
            }

            if (in_array($element, ['plugins', 'themes'], true)) {
                $targetDir = $element . DIRECTORY_SEPARATOR . str_replace(':dirname', $directory, $targetDir);
            }

            $override[$source] ??= sprintf('%s%s/%s.json', $this->overrides, $targetDir, $this->locale());
            $routes[$source]   ??= sprintf('%s/%s.json', $targetRoute, $filename);
        }

        return self::lookup($string, $routes[$source] ?? '', $override[$source] ?? '');
    }

    /**
     * Find a translation in the override file, then in the translation file.
     *
     * @param string $string
     * @param string $path
     * @param string $overridePath
     * @return string
     */
    private static function lookup(string $string, string $path, string $overridePath = ''): string
    {
        foreach ([$overridePath, $path] as $file) {
            if (is_file($file)) {
                $translations = json_decode(file_get_contents($file) ?: '', true);
                if (isset($translations[$string])) {
                    return $translations[$string];
                }
            }
        }

        return $string;
    }
}
