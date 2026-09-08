<?php

declare(strict_types=1);

namespace Expansa\Security;

use Expansa\Facades\Json;
use Expansa\Security\Xss\Kses;

final class Sanitizer
{
    /**
     * Values treated as "false" by {@see self::bool()}.
     */
    private const array FALSY_VALUES = ['false', false, '0', 0, '', null, 'off'];

    /**
     * HTML attributes rendered bare (present/absent) rather than as name="value"
     * by {@see self::attributes()}.
     */
    private const array BOOLEAN_ATTRIBUTES = [
        'accesskey', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable',
        'controls', 'disabled', 'draggable', 'hidden', 'ismap', 'loop', 'multiple',
        'readonly', 'required', 'selected',
    ];

    /**
     * [method, defaultValue, isOwnMethod] triples per raw rule-list string ('trim',
     * 'slug:$login|trim', ...), keyed by that string. The same handful of rule strings are
     * declared once per model (in getSanitizerRules()) but re-parsed and re-checked with
     * is_callable() on every single attribute write, since Model::setAttribute() calls apply()
     * once per attribute — this memoizes both in one lookup instead of two.
     *
     * @var array<string, list<array{0: string, 1: ?string, 2: bool}>>
     */
    private static array $parsedRulesCache = [];

    /**
     * Sanitized data.
     *
     * @var array
     */
    public array $data = [];

    /**
     * Setup sanitizer rules.
     *
     * @param array $fields     Incoming fields and their values.
     * @param array $rules      Sanitizer rules list. Example: 'kebabcase'.
     * @param array $extensions List for custom rules for extend sanitizer.
     */
    public function __construct(
        protected array $fields = [],
        protected array $rules = [],
        protected array $extensions = [],
    ) {} // phpcs:ignore

    /**
     * Setup sanitizer rules via `data` method.
     *
     * @param array $fields
     * @param array $rules
     * @param array $extensions
     * @return Sanitizer
     */
    public function data(array $fields, array $rules, array $extensions = []): Sanitizer
    {
        return new self($fields, $rules, $extensions);
    }

    /**
     * Add custom sanitizing rule or override exist.
     *
     * @param string    $rule     New sanitize rule name.
     * @param callable  $callback Callback for sanitizing.
     * @return Sanitizer
     */
    public function extend(string $rule, callable $callback): Sanitizer
    {
        if (is_callable($callback)) {
            $this->extensions[ $rule ] = $callback;
        }
        return $this;
    }

    /**
     * Splits a rule-list string ('trim', 'slug:$login|trim', ...) into its ['method', defaultValue,
     * isOwnMethod] triples, one per '|'-separated rule. Cached per raw string — see
     * {@see self::$parsedRulesCache}.
     *
     * @return list<array{0: string, 1: ?string, 2: bool}>
     */
    private static function parseRuleList(string $rulesList): array
    {
        if (isset(self::$parsedRulesCache[$rulesList])) {
            return self::$parsedRulesCache[$rulesList];
        }

        $parsed = [];
        foreach (explode('|', $rulesList) as $rule) {
            $parts    = explode(':', $rule, 2);
            $method   = $parts[0];
            $parsed[] = [$method, $parts[1] ?? null, is_callable([self::class, $method])];
        }

        return self::$parsedRulesCache[$rulesList] = $parsed;
    }

    /**
     * Apply sanitizer.
     *
     * @param string $return Field for return.
     * @return mixed
     */
    public function apply(string $return = ''): mixed
    {
        // TODO: !!! if rule not exists throw an Exception
        foreach ($this->rules as $field => $rulesList) {
            $key = null;

            // add support dot notation
            if (str_contains($field, '.')) {
                $parts = explode('.', $field, 2);
                $field = $parts[0];
                $key   = $parts[1] ?? null;
            }

            foreach (self::parseRuleList($rulesList) as [$method, $defaultValue, $isOwnMethod]) {
                $extension = $this->extensions[$method] ?? null;
                $value     = $key !== null
                    ? $this->data[$field][$key] ?? $this->fields[$field][$key] ?? null
                    : $this->data[$field] ?? $this->fields[$field] ?? null;

                // Apply the rule's default only when the incoming value is genuinely
                // absent (null or an empty string) and a default was actually given.
                // empty() would also treat legitimate values like 0, '0', or false as
                // "missing" — e.g. Post::add()'s 'parent_id' => 'absint:0' default
                // string '0' is itself empty(), which used to skip this branch
                // entirely and silently disable that default.
                if (($value ?? '') === '' && ($defaultValue ?? '') !== '') {
                    $value = $defaultValue;

                    // substring "$" at the beginning, means that the default value must be taken from another field
                    if (str_starts_with($defaultValue, '$')) {
                        $referencedField = trim($defaultValue, '$');

                        // Prefer the referenced field's own already-sanitized result (set if it was
                        // processed earlier in this same batch), falling back to its raw incoming
                        // value — needed when the referenced field isn't part of this call's $rules
                        // at all, e.g. Model::setAttribute() sanitizing one attribute at a time.
                        $value = $this->data[$referencedField] ?? $this->fields[$referencedField] ?? '';
                    }
                }

                $data = match (true) {
                    is_callable($extension) => $extension($value, $this),
                    $isOwnMethod             => self::{$method}($value),
                    default                  => null
                };

                if ($key !== null) {
                    $this->data[$field][$key] = $data;
                } else {
                    $this->data[$field] = $data;
                }
            }
        }

        if ($return) {
            return $this->data[$return] ?? null;
        }

        return $this->data;
    }

    /**
     * Get values list.
     *
     * @return array
     */
    public function values(): array
    {
        return array_values($this->apply());
    }

    /**
     * Checks for the presence of an element in the array.
     *
     * @param mixed $value Value to change
     * @param string|null $comparisonValue Value to compare
     * @return string
     */
    public static function exist(mixed $value, ?string $comparisonValue): string
    {
        $comparison = [];
        if ($comparisonValue) {
            $comparison = array_map('trim', explode(',', $comparisonValue));
        }
        return in_array((string) $value, $comparison, true) ? (string) $value : '';
    }

    /**
     * Leads a variable to a string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function string(mixed $value): string
    {
        return (string) $value;
    }

    /**
     * Leads a variable to an array.
     *
     * @param mixed $value Value to change
     * @return array
     */
    public static function array(mixed $value): array
    {
        return is_array($value) ? $value : ( ! empty($value) ? (array) $value : [] );
    }

    /**
     * Leads a variable to number.
     *
     * @param mixed $value Value to change
     * @return int
     */
    public static function int(mixed $value): int
    {
        return (int) $value;
    }

    /**
     * Leads a variable to not negative number.
     *
     * @param mixed $value Value to change
     * @return int
     */
    public static function absint(mixed $value): int
    {
        return abs((int) $value);
    }

    /**
     * Leads a variable to float number.
     *
     * @param mixed $value Value to change
     * @return float
     */
    public static function float(mixed $value): float
    {
        return (float) $value;
    }

    /**
     * Leads a variable to boolean.
     *
     * @param mixed $value Value to change
     * @return bool
     */
    public static function bool(mixed $value): bool
    {
        return ! in_array($value, self::FALSY_VALUES, true);
    }

    /**
     * Leads a variable to json string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function json(mixed $value): string
    {
        return Json::encode($value);
    }

    /**
     * Normalizes a value to a MySQL-compatible datetime string.
     *
     * Attempts to parse the given value using the format 'Y-m-d H:i:s'.
     * Returns the formatted datetime string if valid, or an empty string otherwise.
     *
     * @param mixed $value Input value to convert.
     * @return string Normalized datetime string or empty string if invalid.
     */
    public static function datetime(mixed $value): string
    {
        if (! is_scalar($value) || $value === '') {
            return '';
        }

        $format   = 'Y-m-d H:i:s';
        $datetime = \DateTime::createFromFormat($format, (string) $value);

        return $datetime instanceof \DateTime ? $datetime->format($format) : '';
    }

    /**
     * Sanitizer html markup.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function markup(mixed $value): string
    {
        return ( new Kses() )->apply($value ?? '');
    }

    /**
     * Replaces special characters with HTML entities in the transmitted text, returns formatted text.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function html(mixed $value): string
    {
        return trim(htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * Sanitizer attribute.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function attribute(mixed $value): string
    {
        return trim(htmlspecialchars((string) $value, ENT_QUOTES));
    }

    /**
     * Sanitizer attributes list.
     *
     * @param mixed $attributes Value to change
     * @return string
     */
    public static function attributes(mixed $attributes): string
    {
        if (!is_array($attributes)) {
            return '';
        }

        $atts = [];
        foreach ($attributes as $attribute => $value) {
            $attribute = trim(htmlspecialchars((string) $attribute, ENT_QUOTES));
            $value     = trim(htmlspecialchars((string) $value, ENT_QUOTES));
            if (! $attribute) {
                continue;
            }

            if (in_array($attribute, self::BOOLEAN_ATTRIBUTES, true)) {
                if ($value) {
                    $atts[] = $attribute;
                }
            } else {
                $atts[] = match ($attribute) {
                    'value' => sprintf('%s="%s"', $attribute, $value),
                    default => $value
                        ? sprintf('%s="%s"', $attribute, $value)
                        : ( str_starts_with($attribute, 'u-') ? $attribute : '' ),
                };
            }
        }

        return $atts ? ' ' . implode(' ', $atts) : '';
    }

    /**
     * Value to price.
     *
     * @param mixed $value Value to change
     * @return float
     */
    public static function price(mixed $value): float
    {
        if (! is_scalar($value)) {
            return 0.0;
        }

        $sanitized = preg_replace('/[^0-9.,]/', '', self::trim($value));

        $lastComma = strrpos($sanitized, ',');
        $lastDot   = strrpos($sanitized, '.');

        // Both separators present: whichever appears last is the decimal point
        // ("1,234.56" or "1.234,56"), the other is a thousands separator and is
        // dropped entirely — blindly turning every ',' into '.' (the old
        // behavior) mangled "1,234.56" into 1.234, silently losing the cents
        // and three orders of magnitude.
        if ($lastComma !== false && $lastDot !== false) {
            $sanitized = $lastComma > $lastDot
                ? str_replace('.', '', $sanitized)
                : str_replace(',', '', $sanitized);
        }

        return (float) str_replace(',', '.', $sanitized);
    }

    /**
     * Sanitizes a string from user input or from the database.
     *
     * - Checks for invalid UTF-8,
     * - Converts single `<` characters to entities
     * - Strips all tags
     * - Removes line breaks, tabs, and extra whitespace
     * - Strips percent-encoded characters
     *
     * @param mixed $value Value to change
     * @param bool $keepNewLines
     * @return string
     */
    public static function text(mixed $value, bool $keepNewLines = false): string
    {
        // return an empty string if the input is an object or an array
        if (is_object($value) || is_array($value)) {
            return '';
        }

        // cast the input to a string
        $value = (string) $value;

        // check for valid UTF-8 encoding
        $value = mb_check_encoding($value, 'UTF-8') ? $value : '';

        // if the string contains the `<` character
        if (str_contains($value, '<')) {
            // escape `<` unless followed by a word character
            $value = preg_replace_callback('/<(?!\w)/', fn($matches) => htmlspecialchars($matches[0] ?? ''), $value);
            // remove all HTML tags
            $value = strip_tags($value);
            // replace `<\n` with a safe HTML entity
            $value = str_replace("<\n", "&lt;\n", $value);
        }

        // remove extra spaces, tabs, and newlines if `$keep_newlines` is false
        if (! $keepNewLines) {
            $value = preg_replace('/[\r\n\t ]+/', ' ', $value);
        }
        $value = trim($value);

        // remove percent-encoded characters
        while (preg_match('/%[a-fA-F0-9]{2}/', $value)) {
            $value = preg_replace('/%[a-fA-F0-9]{2}/', '', $value);
        }

        // clean up any extra spaces after removing percent-encoded characters
        return trim(preg_replace('/ +/', ' ', $value));
    }

    /**
     * Sanitizes a multiline string from user input or from the database.
     *
     * The function is like self::text(), but preserves
     * new lines (\n) and other whitespace, which are legitimate
     * input in textarea elements.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function textarea(mixed $value): string
    {
        return self::text($value, true);
    }

    /**
     * Clears the string to use it as a key. The keys are used as different internal IDs.
     * Removes everything from the string except a-z0-9_- and converts the string to lowercase.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function id(mixed $value): string
    {
        return preg_replace('/[^a-z0-9_\-]/', '', self::lowercase($value));
    }

    /**
     * Clears the string to use it as a value of name attribute.
     * Removes everything from the string except a-zA-Z0-9_[].
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function name(mixed $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\[\]]/', '', self::trim($value));
    }

    /**
     * Strips out all characters not allowed in a locale name.
     *
     * @param string $value The locale name to be sanitized.
     * @return string       The sanitized value.
     */
    public static function locale(mixed $value): string
    {
        // limit to A-Z, a-z, 0-9, '_', '-'.
        return preg_replace('/[^A-Za-z0-9_-]/', '', self::trim($value));
    }

    /**
     * Sanitize HTML tag.
     *
     * @param string $value The tag to be sanitized.
     * @return string       The sanitized value.
     */
    public static function tag(mixed $value): string
    {
        // limit to a-z, '-'
        return preg_replace('/[^a-z-]/', '', self::trim($value));
    }

    /**
     * Convert incoming data to valid javascript variable. Support "dot" notation.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function prop(mixed $value): string
    {
        return self::camelcase(self::dot($value));
    }

    /**
     * Convert name attribute as "db[username]" to "db.username".
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function dot(mixed $value): string
    {
        return trim(str_replace([ ']', '[' ], [ '', '.' ], self::lowercase($value)), '.');
    }

    /**
     * Strip whitespaces, tabs, NUL-byte & new line from the beginning & end of a string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function trim(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * To uppercase.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function uppercase(mixed $value): string
    {
        return mb_strtoupper(self::trim($value));
    }

    /**
     * Lowercase string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function lowercase(mixed $value): string
    {
        return mb_strtolower(self::trim($value));
    }

    /**
     * Capitalize string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function capitalize(mixed $value): string
    {
        return ucwords(self::trim($value));
    }

    /**
     * Capitalize first letter.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function ucfirst(mixed $value): string
    {
        return mb_ucfirst(self::trim($value));
    }

    /**
     * Pascal case string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function pascalcase(mixed $value): string
    {
        $value = self::trim($value);
        $value = str_replace(['_', '-'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);

        return ucfirst($value);
    }

    /**
     * Camel case string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function camelcase(mixed $value): string
    {
        $value = self::trim($value);
        $value = str_replace(['_', '-'], ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);

        return lcfirst($value);
    }

    /**
     * Snake case string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function snakecase(mixed $value): string
    {
        $value = self::trim($value);

        // replacing spaces with underscores
        $str = preg_replace(['/\s+/', '#-#'], '_', $value);
        // insert underscores before each capital letter
        $str = preg_replace('/(.)(?=[A-Z])/u', '$1_', $str);

        return strtolower($str);
    }

    /**
     * Kebab case string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function kebabcase(mixed $value): string
    {
        // replacing spaces and underscores with hyphens
        $str = preg_replace(['/[\s_]+/', '/(.)(?=[A-Z])/u'], ['-', '$1-'], self::trim($value));

        return strtolower($str);
    }

    /**
     * Flat case string.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function flatcase(mixed $value): string
    {
        return strtolower(str_replace(['-', '_', ' '], '', self::trim($value)));
    }

    /**
     * Password hash.
     *
     * @param  string $value
     * @return string
     */
    public static function hash(mixed $value): string
    {
        return password_hash(self::trim($value), PASSWORD_DEFAULT);
    }

    /**
     * Filters string for valid email characters.
     *
     * @param string $value
     * @return string
     */
    public static function email(mixed $value): string
    {
        return strval(filter_var(self::trim($value), FILTER_SANITIZE_EMAIL));
    }

    /**
     * Converts email addresses characters to HTML entities to block spam bots.
     *
     * @param string $value Email address.
     * @return string       Converted email address.
     */
    public static function emailAntiSpam(mixed $value): string
    {
        $value = self::trim($value);

        $emailNoSpamAddress = '';
        for ($i = 0, $len = strlen($value); $i < $len; $i++) {
            $j = rand(0, 1);

            if (0 === $j) {
                $emailNoSpamAddress .= '&#' . ord($value[ $i ]) . ';';
            } elseif (1 === $j) {
                $emailNoSpamAddress .= $value[ $i ];
            } elseif (2 === $j) {
                $emailNoSpamAddress .= sprintf('%%%02X', ord($value[$i]));
            }
        }
        return str_replace('@', '&#64;', $emailNoSpamAddress);
    }

    /**
     * Filters string for valid URL characters.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function url(mixed $value): string
    {
        return strval(filter_var(self::trim($value), FILTER_SANITIZE_URL));
    }

    /**
     * Sanitize url for use inside href attribute.
     *
     * @param string $value
     * @return string
     */
    public static function href(mixed $value): string
    {
        if ($value === '') {
            return $value;
        }

        $value = str_replace(' ', '%20', ltrim($value));
        $value = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $value);

        if ($value === '') {
            return $value;
        }

        if (stripos($value, 'mailto:') !== 0) {
            $value = str_replace(['%0d', '%0a', '%0D', '%0A'], '', $value);
        }

        // normalize slashes
        $value = str_replace(';//', '://', $value);

        if (preg_match('~^(?:(?:https?|ftp)://[^@]+(?:/.*)?|(?:mailto|tel|sms):.+|[/?#].*|[^:]+)$~Di', $value)) {
            return trim($value);
        }

        return '';
    }

    /**
     * Filters string for valid path syntax, with optional trailing slash.
     *
     * @param mixed $value Value to change
     * @return string
     */
    public static function path(mixed $value): string
    {
        // Remove whitespace, spaces, leading and trailing slashes
        $path = self::trim(preg_replace('/\s+/', '', (string) $value));

        // Convert all invalid slashes to one single forward slash
        return strtr(
            $path,
            [
                '//'   => '/',
                '\\'   => '/',
                '\\\\' => '/',
            ]
        );
    }

    /**
     * Sanitizes an HTML classname to ensure it only contains valid characters.
     *
     * Strips the string down to A-Z,a-z,0-9,_,-. If this results in an empty
     * string then it will return the alternative value supplied.
     *
     * @param mixed $value The classname to be sanitized
     *
     * @return string The sanitized value
     */
    public static function class(mixed $value): string
    {
        $sanitized = [];
        $classes   = explode(' ', self::trim($value));

        foreach ($classes as $class) {
            // Strip out any %-encoded octets.
            $class = preg_replace('|%[a-fA-F0-9][a-fA-F0-9]|', '', $class);

            // Limit to A-Z, a-z, 0-9, '_', '-' & ':' symbol for support tailwind.
            $class = preg_replace('/[^A-Za-z0-9:_-]/', '', $class);

            $sanitized[] = self::trim($class);
        }

        return implode(' ', array_filter($sanitized));
    }

    /**
     * Normalize EOL characters and strip duplicate whitespace.
     *
     * @param mixed $value Value to change
     *
     * @return string the normalized string
     */
    public static function whitespace(mixed $value): string
    {
        return preg_replace(['/\n+/', '/[ \t]+/'], ["\n", ' '], str_replace("\r", "\n", self::trim($value)));
    }

    /**
     * Properly strip all HTML tags including script and style.
     *
     * This differs from strip_tags() because it removes the contents of
     * the `<script>` and `<style>` tags. E.g. `strip_tags( '<script>something</script>' )`
     * will return 'something'. `Sanitizer::tags()` will return ''
     *
     * @param  string $value String containing HTML tags.
     * @return string        The processed string.
     */
    public static function tags(string $value): string
    {
        $value = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $value);
        $value = strip_tags($value);
        $value = preg_replace('/[\r\n\t ]+/', ' ', $value);

        return self::trim($value);
    }

    /**
     * Sanitizer a mime type.
     *
     * @param mixed $value Mime type.
     * @return string      Sanitized mime type.
     */
    public static function mime(mixed $value): string
    {
        return preg_replace('/[^-+*.a-zA-Z0-9\/]/', '', self::trim($value));
    }

    /**
     * Sanitizes a hex color with or without a hash.
     *
     * @param string $value Color in HEX format
     * @return string|null  3 or 6 digit hex color with or without #
     */
    public static function hex(mixed $value): ?string
    {
        $value = self::trim($value);
        if (! str_contains($value, '#')) {
            $value = '#' . $value;
        }

        // 3 or 6 hex digits, or the empty string.
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $value)) {
            return $value;
        }

        return '';
    }

    /**
     * Sanitizes a string into a slug, which can be used in URLs, user nickname or HTML attributes.
     *
     * @param string  $value Value of slug.
     * @return string
     */
    public static function slug(string $value): string
    {
        $value = strip_tags($value);
        // Preserve escaped octets.
        $value = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $value);
        // Remove percent signs that are not part of an octet.
        $value = str_replace('%', '', $value);
        // Restore octets.
        $value = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $value);

        $value = strtolower($value);

        // Remove HTML entities.
        $value = preg_replace('/&.+?;/', '', $value);
        $value = str_replace('.', '-', $value);

        $value = preg_replace('/[^%a-z0-9 _-]/', '', $value);
        $value = preg_replace('/\s+/', '-', $value);
        $value = preg_replace('|-+|', '-', $value);

        return self::trim($value);
    }

    /**
     * Sanitize a login.
     *
     * @param  mixed  $value Incoming login.
     * @return string
     */
    public static function login(string $value): string
    {
        $value = self::trim($value);

        // Strip all tags.
        $value = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $value);
        $value = strip_tags($value);
        $value = preg_replace('/[\r\n\t ]+/', ' ', $value);

        $value = self::accents($value);
        // Remove percent-encoded characters.
        $value = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $value);
        // Remove HTML entities.
        $value = preg_replace('/&.+?;/', '', $value);

        // Consolidate contiguous whitespace.
        return preg_replace('|\s+|', '', $value);
    }

    /**
     * Removes accent from strings.
     *
     * Safe::accents("ªºÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿØ");
     *          echo: ooAAAAAACEEEEIIIINOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyyO
     *
     * @param  mixed  $value Incoming login.
     * @return string
     */
    public static function accents(string $value): string
    {
        if (! preg_match('/[\x80-\xff]/', $value)) {
            return $value;
        }

        // converting accents in HTML entities
        $value = htmlentities($value, ENT_NOQUOTES, 'utf-8');

        // replacing the HTML entities to extract the first letter
        // examples: "&ecute;" => "e", "&Ecute;" => "E", "à" => "a" ...
        $value = preg_replace(
            '#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml|caron|lig|rdf|rdm);#',
            '\1',
            $value
        );

        // replacing ligatures, e.g.: "œ" => "oe", "Æ" => "AE"
        $value = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $value);

        // removing the remaining bits
        return preg_replace('#&[^;]+;#', '', $value);
    }

    /**
     * Sanitizes a filename, replacing whitespace with dashes.
     *
     * Removes special characters that are illegal in filenames on certain
     * operating systems and special characters requiring special escaping
     * to manipulate at the command line. Replaces spaces and consecutive
     * dashes with a single dash. Trims period, dash and underscore from beginning
     * and end of filename. It is not guaranteed that this function will return a
     * filename that is allowed to be uploaded.
     *
     * @param string $value The filename to be sanitized.
     * @return string       The sanitized filename.
     */
    public static function filename(string $value): string
    {
        $filename  = pathinfo($value, PATHINFO_FILENAME);
        $extension = pathinfo($value, PATHINFO_EXTENSION);

        // remove accents
        $filename = self::accents($filename);

        // remove not allowed symbols
        $specialChars = '"!@#$%&*()~+=|{[}]/?;:.,\\\'<>«»”“’`°ºª�' . chr(0);
        $filename     = str_replace(mb_str_split($specialChars), '', $filename);

        // remove not allowed symbols & letters with diacritics
        $letters      = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr';
        $replacements = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyrr';
        $filename     = str_replace(mb_str_split($letters), mb_str_split($replacements), $filename);

        // remove whitespaces
        $filename = str_replace(' ', '-', $filename);

        // remove duplicate hyphens, tabs & new lines
        $filename = preg_replace('/[\r\n\t -]+/', '-', $filename);
        $filename = preg_replace('/[-\s]+/', '-', $filename);
        $filename = trim($filename, '.-_');

        return sprintf('%s.%s', $filename, $extension);
    }

    /**
     * Sanitize DB table name.
     *
     * @param string $value The DB table name to be sanitized.
     * @return string       The sanitized DB table name.
     */
    public static function tablename(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9 _-]/', '', $value);

        // replacing spaces and hyphens with underscores
        $value = str_replace([' ', '-'], '_', $value);

        // trim the name to 64 characters (the maximum length for a table name in MySQL)
        return substr($value, 0, 64);
    }
}
