<?php

declare(strict_types=1);

namespace App\Api\Translations;

use Expansa\Facades\Disk;
use Expansa\Facades\I18n;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;
use Expansa\Http\Request;

/**
 * Strings of a project to translate and the translations saved from the dashboard. A string with a context
 * is the key `context\x04string`, the one of the translation files, see I18n::translate().
 *
 * @package App\Api\Translations
 */
final class TranslationsService
{
    /**
     * Separator of a context and a string in a translation key.
     */
    public const string CONTEXT = "\x04";

    /**
     * Folders of the core: its strings are translated by the dashboard files.
     */
    private const array CORE = ['app', 'dashboard', 'expansa'];

    /**
     * Functions and I18n methods whose first argument is a translatable string.
     */
    private const array FUNCTIONS = [
        't'                        => true,
        't_attr'                   => true,
        'translate'                => true,
        'translateAttribute'       => true,
        'translatePlural'          => true,
        'translateAttributePlural' => true,
    ];

    /**
     * Get the strings of a project to translate, see scan().
     *
     * @param Request $request
     * @return array{items: list<array{source: string, value: string}>}
     */
    public function get(Request $request): array
    {
        $items = array_map(
            fn (string $source) => ['source' => $source, 'value' => ''],
            $this->scan((string) ($request->post['project'] ?? '')),
        );

        return ['items' => $items];
    }

    /**
     * Find the translatable strings in the PHP files of a project: the literal first arguments of t(), t_attr()
     * and the translate methods of I18n, joined with their `context:`; unique, sorted case-insensitively.
     *
     * @param string $project `core`, or a plugin or theme id: `plugins/seo`.
     * @return list<string>
     */
    public function scan(string $project): array
    {
        $paths = $project === 'core' ? self::CORE : [Safe::path($project)];

        $strings = [];
        foreach ($paths as $path) {
            foreach (Disk::dir(EX_PATH . $path)->files('*.php', 10) as $file) {
                $code     = (string) Disk::file($file)->read();
                $strings += $this->strings(str_ends_with($file, '.blade.php') ? self::blade($code) : $code);
            }
        }

        return self::sort(array_keys($strings));
    }

    /**
     * Save the translations of a project to its override file of the current locale, the one I18n reads first;
     * a translation that is empty or the same as in the translation file is removed from it.
     *
     * @param Request $request
     * @return array
     */
    public function update(Request $request): array
    {
        [$project, $translations] = Safe::data($request->post, [
            'project'      => 'trim',
            'translations' => 'array',
        ])->values();

        if ($project === '' || $translations === []) {
            return [];
        }

        $file  = Disk::file($this->overridePath($project));
        $saved = $this->read($this->overridePath($project));
        $base  = $this->read($this->translationPath($project));

        foreach ($translations as $row) {
            // a disabled field of plural forms sends no value
            $key = self::key((string) ($row['context'] ?? ''), (string) ($row['source'] ?? ''));
            if ($key === '' || ! isset($row['value'])) {
                continue;
            }

            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '' || $value === ($base[$key] ?? null)) {
                unset($saved[$key]);
            } else {
                $saved[$key] = $value;
            }
        }

        $saved = array_replace(array_flip(self::sort(array_keys($saved))), $saved);

        $file->write(Json::encode($saved, pretty: true), append: false);

        return [];
    }

    /**
     * Get the translations of a project in the current locale: its translation file, then its override file.
     *
     * @param string $project
     * @return array<string, string|string[]>
     */
    public function translations(string $project): array
    {
        return array_replace($this->read($this->translationPath($project)), $this->read($this->overridePath($project)));
    }

    /**
     * Sort translation keys by their text case-insensitively, the contexts of one text right after it.
     *
     * @param array<int|string> $keys
     * @return list<string>
     */
    public static function sort(array $keys): array
    {
        $keys = array_map('strval', $keys);
        usort($keys, fn (string $a, string $b) => strcasecmp(self::text($a), self::text($b)) ?: strcmp($a, $b));

        return $keys;
    }

    /**
     * Build a translation key from a context and a string.
     *
     * @param string $context
     * @param string $string
     * @return string
     */
    public static function key(string $context, string $string): string
    {
        return $context === '' || $string === '' ? $string : $context . self::CONTEXT . $string;
    }

    /**
     * Get the context of a translation key, empty for a key without one.
     *
     * @param string $key
     * @return string
     */
    public static function context(string $key): string
    {
        return str_contains($key, self::CONTEXT) ? strstr($key, self::CONTEXT, true) : '';
    }

    /**
     * Get the string of a translation key without its context.
     *
     * @param string $key
     * @return string
     */
    public static function text(string $key): string
    {
        $position = strpos($key, self::CONTEXT);

        return $position === false ? $key : substr($key, $position + 1);
    }

    /**
     * Find the translatable strings of PHP code by its tokens: a call of a translation function
     * with a literal string first; `context:` with a literal value joins it to the key.
     *
     * @param string $code
     * @return array<string, true> Keys of the strings.
     */
    private function strings(string $code): array
    {
        if (! str_contains($code, 't(') && ! str_contains($code, 't_attr(') && ! str_contains($code, 'translate')) {
            return [];
        }

        $tokens = array_values(array_filter(
            token_get_all($code),
            fn (array|string $token) => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));

        $strings = [];
        foreach ($tokens as $i => $token) {
            if (! is_array($token) || $token[0] !== T_STRING || ! isset(self::FUNCTIONS[$token[1]]) || ($tokens[$i + 1] ?? '') !== '(') {
                continue;
            }

            // a method or a function declaration of the same name is not a call: only `I18n::` and plain calls count
            $previous = $tokens[$i - 1] ?? '';
            $method   = is_array($previous) && $previous[0] === T_DOUBLE_COLON;
            if ($method !== str_starts_with($token[1], 'translate')) {
                continue;
            }
            if (is_array($previous) && in_array($previous[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_NEW], true)) {
                continue;
            }

            $first = $tokens[$i + 2] ?? '';
            if (! is_array($first) || $first[0] !== T_CONSTANT_ENCAPSED_STRING || ! in_array($tokens[$i + 3] ?? '', [',', ')'], true)) {
                continue;
            }

            $strings[self::key($this->contextArgument($tokens, $i + 3), self::literal($first[1]))] = true;
        }

        return $strings;
    }

    /**
     * Find the literal `context:` argument of a call, from a token after its first argument to the closing parenthesis.
     *
     * @param array $tokens
     * @param int   $start
     * @return string Empty without one.
     */
    private function contextArgument(array $tokens, int $start): string
    {
        $depth = 0;
        for ($i = $start, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token === '(' || $token === '[') {
                $depth++;
            } elseif ($token === ')' || $token === ']') {
                if ($depth-- === 0) {
                    return '';
                }
            } elseif (
                $depth === 0
                && is_array($token) && $token[0] === T_STRING && $token[1] === 'context'
                && ($tokens[$i + 1] ?? '') === ':'
                && is_array($tokens[$i + 2] ?? '') && $tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING
            ) {
                return self::literal($tokens[$i + 2][1]);
            }
        }

        return '';
    }

    /**
     * Turn the echoes and the directive arguments of a Blade template into PHP code for the tokenizer:
     * `{{ t('Save') }}` and `@if (t('Save'))` become `<?php t('Save'); ?>`.
     *
     * @param string $code
     * @return string
     */
    private static function blade(string $code): string
    {
        return (string) preg_replace_callback(
            '/\{\{(.*?)\}\}|\{!!(.*?)!!\}|@\w+\s*(\((?:[^()]++|(?3))*\))/s',
            fn (array $m) => '<?php ' . ($m[1] ?: ($m[2] ?? '') ?: ($m[3] ?? '')) . '; ?>',
            $code,
        );
    }

    /**
     * Value of a PHP string literal without interpolation: `'it\'s'` and `"line\n"`.
     *
     * @param string $literal
     * @return string
     */
    private static function literal(string $literal): string
    {
        $body = substr($literal, 1, -1);

        if ($literal[0] === "'") {
            return strtr($body, ['\\\\' => '\\', "\\'" => "'"]);
        }

        // stripcslashes() knows the C escapes; PHP adds `\$` and `\u{...}`
        $body = preg_replace_callback('/\\\\u\{([0-9a-fA-F]+)\}/', fn (array $m) => mb_chr((int) hexdec($m[1])), $body);

        return stripcslashes(str_replace('\\$', '$', $body));
    }

    /**
     * Read a translation file, empty when it is missing or broken.
     *
     * @param string $path
     * @return array<string, string|string[]>
     */
    private function read(string $path): array
    {
        return (array) (Json::decode(Disk::file($path)->read(), true) ?? []);
    }

    /**
     * Translation file of a project in the current locale: the dashboard one for the core.
     *
     * @param string $project
     * @return string
     */
    private function translationPath(string $project): string
    {
        $directory = $project === 'core' ? EX_DASHBOARD : EX_PATH . Safe::path($project) . '/';

        return $directory . 'i18n/' . I18n::locale() . '.json';
    }

    /**
     * Override file of a project in the current locale, the path of I18n::configure() `overrides`:
     * `dashboard` for the core, the extension id for a plugin or theme.
     *
     * @param string $project
     * @return string
     */
    private function overridePath(string $project): string
    {
        return sprintf('%s%s/%s.json', EX_I18N, $project === 'core' ? 'dashboard' : Safe::path($project), I18n::locale());
    }
}
