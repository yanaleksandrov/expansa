<?php

declare(strict_types=1);

namespace Expansa\Translation;

use Closure;
use Expansa\Translation\Internal\Forms;
use Expansa\Translation\Internal\Markdown;
use Expansa\Translation\Internal\PluralRule;
use InvalidArgumentException;
use NumberFormatter;

/**
 * Translations with placeholders, plural forms and basic Markdown, the locale of the request and the language list.
 * The I18n facade instance; t() and t_attr() are its shortcuts for templates.
 *
 * ```php
 * t('See the [documentation](:pageLink) to resolve this issue', 'https://example.com');
 * I18n::translatePlural(':count file|:count files', 5); // '5 files'
 * ```
 *
 * @package Expansa\Translation
 */
final class Manager
{
    /**
     * Rule of the languages without one: English, the singular for 1 only.
     */
    private const string DEFAULT_PLURAL = 'n != 1';

    /**
     * Source directories and the translation directories they map to.
     *
     * @var array<string, string>
     */
    private array $routes = [];

    /**
     * Pattern of a translation file name for sprintf(), gets the locale.
     *
     * @var string
     */
    private string $pattern = '';

    /**
     * Directory with translation overrides, e.g. edited in the dashboard.
     *
     * @var string
     */
    private string $overrides = '';

    /**
     * Returns the language list, called once on the first use.
     *
     * @var null|Closure
     */
    private ?Closure $languageSource = null;

    /**
     * Returns the locale chosen for the request, e.g. by the user, or null to detect it from Accept-Language.
     *
     * @var null|Closure
     */
    private ?Closure $localeSource = null;

    /**
     * Languages from the source, null until the first use.
     *
     * @var array[]|null
     */
    private ?array $languages = null;

    /**
     * Languages by field and value, built for a field on its first lookup.
     *
     * @var array<string, array<string, array>>
     */
    private array $languageIndex = [];

    /**
     * Number formatters by locale.
     *
     * @var array<string, NumberFormatter>
     */
    private array $formatters = [];

    /**
     * Counts formatted for the locale, by count.
     *
     * @var array<int, string>
     */
    private array $numbers = [];

    /**
     * Compiled plural rules by locale.
     *
     * @var array<string, Closure>
     */
    private array $pluralRules = [];

    /**
     * Translation file and override file of each source file, resolved once per source.
     *
     * @var array<string, array{string, string}>
     */
    private array $sources = [];

    /**
     * Decoded translation files by path, false for a missing or invalid file.
     *
     * @var array<string, array<string, string>|false>
     */
    private array $files = [];

    /**
     * Locale of the request with a dash, false if it can not be detected.
     *
     * @var null|false|string
     */
    private string|false|null $requestLocale = null;

    /**
     * Set the translation lookup and the language list, replacing the previous configuration.
     *
     * @param array<string, string> $routes    Source directories and their translation directories.
     * @param string                $pattern   sprintf() pattern of a translation file name, gets the locale.
     * @param string                $overrides Directory with translation overrides.
     * @param Closure|null          $languages `fn (): array` of languages with `locale`, `iso_639_1`, `plural`...,
     *                                         called on the first use; without it the bundled Languages::all().
     * @param Closure|null          $locale    `fn (): ?string` chosen locale, e.g. of the user, called on the first use;
     *                                         null or an empty string falls back to Accept-Language.
     * @return void
     */
    public function configure(array $routes, string $pattern, string $overrides = '', ?Closure $languages = null, ?Closure $locale = null): void
    {
        $this->routes         = $routes;
        $this->pattern        = $pattern;
        $this->overrides      = $overrides;
        $this->languageSource = $languages;
        $this->localeSource   = $locale;
        $this->requestLocale  = null;
        $this->languages      = null;
        $this->languageIndex  = [];
        $this->formatters     = [];
        $this->numbers        = [];
        $this->pluralRules    = [];
        $this->sources        = [];
        $this->files          = [];
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
     * Markdown gives bold, italic, headers, quotes, images and links. Blocks of plural forms take the count
     * as the first value: `{file|files}` in the order of the plural rule, `=0 No files` for an exact count,
     * `#` and `:count` for the number in the format of the locale; `\{` is a literal brace.
     *
     * ```php
     * t('Hi, ::Firstname, you have :count\st none closed "::TASKNAME" task.', 'john', 1, 'test');
     * // 'Hi, John, you have 1st none closed "TEST" task.'
     * t(':count {item|items} in :folder', 5, 'Docs');
     * // ru, ":count {товар|товара|товаров} в :folder": '5 товаров в Docs'
     * ```
     *
     * @param string $string
     * @param mixed  ...$args Values of the placeholders, in order of appearance; the count first for plural forms.
     * @return string
     */
    public function translate(string $string, mixed ...$args): string
    {
        if ($args !== [] && Forms::has($string) && is_numeric($args[0])) {
            return $this->translateForms($string, (int) array_shift($args), $args);
        }

        return Markdown::render($this->fill($this->get($string), $args));
    }

    /**
     * Translate a string with blocks of plural forms for a count, the same as translate() with the count first.
     *
     * @param string $forms   String with `{...|...}` blocks, see translate().
     * @param int    $count
     * @param mixed  ...$args Values of the other placeholders.
     * @return string
     */
    public function translatePlural(string $forms, int $count, mixed ...$args): string
    {
        return $this->translate($forms, $count, ...$args);
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
        return $this->toAttribute($this->translate($string, ...$args));
    }

    /**
     * Translate a string with blocks of plural forms for an HTML attribute value, see translatePlural().
     *
     * @param string $forms
     * @param int    $count
     * @param mixed  ...$args
     * @return string
     */
    public function translateAttributePlural(string $forms, int $count, mixed ...$args): string
    {
        return $this->translateAttribute($forms, $count, ...$args);
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
     * Get the locale of the request: the chosen one of configure(), otherwise from the Accept-Language header.
     * A bare language code like `ru` becomes the locale of that language in the list: `ru-RU`.
     *
     * @param string $default Locale when none is chosen and the header is missing or can not be parsed.
     * @return string
     */
    public function locale(string $default = 'en-US'): string
    {
        if ($this->requestLocale === null) {
            // a translation inside the callback gets the default instead of calling it again
            $this->requestLocale = false;

            $locale = (string) ($this->localeSource === null ? '' : ($this->localeSource)());
            if ($locale === '' && function_exists('locale_accept_from_http')) {
                $locale = (string) locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? $default);
            }

            $locale = str_replace('_', '-', $locale);
            if ($locale !== '' && ! str_contains($locale, '-')) {
                $locale = $this->language(strtolower($locale), 'iso_639_1')['locale'] ?? $locale;
            }

            $this->requestLocale = $locale ?: false;
        }

        return $this->requestLocale ?: $default;
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
        // the first language wins when several share a value, as a linear search would give
        $this->languageIndex[$getBy] ??= array_column(array_reverse($this->getLanguages()), null, $getBy);

        return $this->languageIndex[$getBy][$value] ?? [];
    }

    /**
     * Get the options of a language select: flag and name by locale.
     *
     * @return array<string, array{flag: string, content: string}>
     */
    public function languageOptions(): array
    {
        $options = [];
        foreach ($this->getLanguages() as $language) {
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
     * Translation of a string from the files of the code that asked for it: a plugin or theme
     * uses its own directory, the rest the directory of its route. The override file wins.
     *
     * @param string $string
     * @param bool   $forms  Keep a list of whole sentences of a string with forms instead of its first one.
     * @return ($forms is true ? string|string[] : string) The translation, or the string itself if there is none.
     */
    private function get(string $string, bool $forms = false): string|array
    {
        if ($this->routes === []) {
            return $string;
        }

        $source = $this->source();
        if ($source === '') {
            return $string;
        }

        [$file, $override] = $this->sources[$source] ??= $this->resolve($source);

        $translation = $this->lookup($string, $file, $override);

        return $forms || ! is_array($translation) ? $translation : (string) reset($translation);
    }

    /**
     * File of the first caller outside the framework core: t(), the facade and the core helpers
     * that translate on behalf of their caller live in it.
     *
     * @return string Path with forward slashes, empty when the call stack has no file.
     */
    private function source(): string
    {
        $core   = self::slashes(dirname(__DIR__)) . '/';
        $source = '';

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12) as $frame) {
            if (! isset($frame['file'])) {
                continue;
            }

            $source = self::slashes($frame['file']);
            if (! str_starts_with($source, $core)) {
                break;
            }
        }

        return $source;
    }

    /**
     * Translation file and override file of a source file, by the first route containing it;
     * `:dirname` in a route target is the directory right below the route: the plugin or theme.
     *
     * @param string $source
     * @return array{string, string} Empty paths when no route contains the source.
     */
    private function resolve(string $source): array
    {
        $locale = $this->locale();

        foreach ($this->routes as $route => $target) {
            $route = rtrim(self::slashes($route), '/') . '/';
            if (! str_starts_with($source, $route)) {
                continue;
            }

            $target   = rtrim(self::slashes($target), '/');
            $dirname  = strtok(substr($source, strlen($route)), '/') ?: '';
            $relative = str_contains($target, ':dirname') ? basename(dirname($target)) . '/' . $dirname : basename($target);
            $target   = str_replace(':dirname', $dirname, $target);

            return [
                sprintf('%s/%s.json', $target, sprintf($this->pattern, $locale)),
                $this->overrides === '' ? '' : sprintf('%s/%s/%s.json', rtrim(self::slashes($this->overrides), '/'), $relative, $locale),
            ];
        }

        return ['', ''];
    }

    /**
     * Path with forward slashes, to compare Windows and Unix paths alike.
     *
     * @param string $path
     * @return string
     */
    private static function slashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Escape the string and fill its placeholders.
     *
     * @param string   $string
     * @param array    $args  Values in order of appearance.
     * @param string|null $count Value of `:count` in a string with forms, null for a plain translation.
     * @return string
     */
    private function fill(string $string, array $args, ?string $count = null): string
    {
        $string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // no values or no placeholders: skip the regular expression
        if (($args === [] && $count === null) || strpbrk($string, ':%') === false) {
            return $string;
        }

        $index = 0;

        return preg_replace_callback(
            '{
                (:{1,2})           # (1) one or two colons
                (\w+)              # (2) placeholder name
                (?:\\\\([^:]+))?   # (3) optional suffix after a backslash
                |
                %[sd]
            }x',
            function (array $matches) use ($args, $count, &$index): string {
                if ($matches[0] === '%s' || $matches[0] === '%d') {
                    return array_key_exists($index, $args) ? (string) $args[$index++] : $matches[0];
                }

                $placeholder = $matches[2];

                if ($count !== null && $placeholder === 'count') {
                    $value = $count;
                } elseif (array_key_exists($index, $args)) {
                    $value = (string) $args[$index++];
                } else {
                    return $matches[0];
                }

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

    /**
     * Translate a string with blocks of forms: a translation given as a list holds whole sentences,
     * one per plural form; `#` and `:count` become the count formatted for the locale.
     *
     * @param string $string
     * @param int    $count
     * @param array  $args   Values of the other placeholders.
     * @return string
     */
    private function translateForms(string $string, int $count, array $args): string
    {
        $rule        = $this->pluralRules[$this->locale()] ??= $this->compilePluralRule();
        $number      = $this->numbers[$count] ??= $this->number($count);
        $translation = $this->get($string, forms: true);

        if (is_array($translation)) {
            $translation = str_replace('#', $number, (string) ($translation[$rule(abs($count))] ?? end($translation)));
        }

        return Markdown::render($this->fill(Forms::choose($translation, $count, $rule, $number), $args, $number));
    }

    /**
     * Format a count for the locale: `12 345` in Russian, `12,345` in English; digits only without intl.
     *
     * @param int $count
     * @return string
     */
    private function number(int $count): string
    {
        if (! class_exists(NumberFormatter::class, false)) {
            return (string) $count;
        }

        $formatter = $this->formatters[$this->locale()] ??= new NumberFormatter($this->locale(), NumberFormatter::DECIMAL);

        return $formatter->format($count) ?: (string) $count;
    }

    /**
     * Compile the plural rule of the request locale, by the locale or by its language code.
     * A broken rule of a plugin language falls back to English instead of breaking the page.
     *
     * @return Closure fn (int $n): int
     */
    private function compilePluralRule(): Closure
    {
        $locale   = $this->locale();
        $language = $this->language($locale) ?: $this->language(strtolower(strtok($locale, '-')), 'iso_639_1');

        try {
            return PluralRule::compile($language['plural'] ?? self::DEFAULT_PLURAL);
        } catch (InvalidArgumentException) {
            return PluralRule::compile(self::DEFAULT_PLURAL);
        }
    }

    /**
     * Get the languages: from the configured source, Languages::all() without one; read once.
     *
     * @return array[]
     */
    public function getLanguages(): array
    {
        return $this->languages ??= $this->languageSource === null ? Languages::all() : ($this->languageSource)();
    }

    /**
     * Turn a translation into an HTML attribute value: plain text, escaped once.
     *
     * @param string $html
     * @return string
     */
    private function toAttribute(string $html): string
    {
        // the translation is already escaped, decode once to avoid "&amp;amp;"
        return trim(htmlspecialchars(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES));
    }

    /**
     * Find a translation in the override file, then in the translation file; files are read once.
     *
     * @param string $string
     * @param string $path
     * @param string $overridePath
     * @return string|string[]
     */
    private function lookup(string $string, string $path, string $overridePath = ''): string|array
    {
        foreach ([$overridePath, $path] as $file) {
            if ($file === '') {
                continue;
            }

            $this->files[$file] ??= is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: false) : false;

            if (isset($this->files[$file][$string])) {
                return $this->files[$file][$string];
            }
        }

        return $string;
    }
}
