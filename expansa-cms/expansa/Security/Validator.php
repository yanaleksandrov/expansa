<?php

declare(strict_types=1);

namespace Expansa\Security;

use Closure;
use DateTime;

/**
 * Validates fields against rules: `'field' => 'required|lengthMin:2'`, a rule argument after the colon
 * is a value or the name of another field. Error messages are translated by the callback of configure().
 *
 * ```php
 * $validator = Validator::data($_POST, [
 *     'email' => 'required|email',
 *     'age'   => 'numeric|min:18',
 *     'from'  => 'date|earlier:to',
 *     'time'  => 'time:H:i:s',
 * ])->extend('time', t('Time must be in \'%s\' format'), function (Validator $validator, $value, $format) {
 *     $time = DateTime::createFromFormat($format, $value);
 *
 *     return $time && $time->format($format) === $value;
 * })->extend('age:numeric', t('Age must be a number'))->apply();
 *
 * $validator->isValid();
 * $validator->errors; // ['age' => ['Age must be a number', 'Must be at least 18.']]
 * ```
 *
 * @package Expansa\Security
 */
final class Validator
{
    /**
     * Default error messages by rule, with the arguments of their placeholders.
     */
    private const array MESSAGES = [
        'accepted'     => ['Must be accepted.'],
        'alpha'        => ['Must contain only letters.'],
        'alphanumeric' => ['Must contain only letters and/or numbers.'],
        'hex'          => ['The color format should be :format.', 'HEX'],
        'hsl'          => ['The color format should be :format.', 'HSL'],
        'hsla'         => ['The color format should be :format.', 'HSLA'],
        'rgb'          => ['The color format should be :format.', 'RGB'],
        'rgba'         => ['The color format should be :format.', 'RGBA'],
        'date'         => ['Is not a valid date.'],
        'later'        => ["Must be a date after '%s'."],
        'earlier'      => ["Must be a date before '%s'."],
        'different'    => ["Must be different from '%s'."],
        'email'        => ['Is not a valid email address.'],
        'equals'       => ["Must be the same as '%s'."],
        'ip'           => ['Is not a valid IP address.'],
        'ipv4'         => ['Is not a valid IPv4 address.'],
        'ipv6'         => ['Is not a valid IPv6 address.'],
        'length'       => ['Must be %d characters long.'],
        'lengthMin'    => ['Must be at least %d characters long.'],
        'lengthMax'    => ['Must not exceed %d characters.'],
        'mac'          => ['Is not a valid MAC address.'],
        'max'          => ['Must be no more than %s.'],
        'min'          => ['Must be at least %s.'],
        'numeric'      => ['Must be numeric.'],
        'required'     => ['Is required.'],
        'regex'        => ['Has an invalid format.'],
        'similar'      => ["Must match '%s'."],
        'slug'         => ['Must contain only letters, numbers, dashes and underscores.'],
        'tld'          => ['Is not a valid top-level domain (TLD).'],
        'url'          => ['Is not a valid URL.'],
        'uuid'         => ['Is not a valid UUID.'],
        'type'         => ['This type of file is not allowed.'],
        'minSize'      => ['File size is too small. Must be greater than or equal to %s.'],
        'maxSize'      => ['File size is too large. Must be less than %s.'],
        'extension'    => ['Invalid file extension. Accepted extensions are: %s.'],
    ];

    /**
     * Translates a message with the arguments of its `:name` placeholders.
     *
     * @var null|Closure
     */
    private static ?Closure $translate = null;

    /**
     * Translated MESSAGES, filled once per process.
     *
     * @var array<string, string>
     */
    private static array $translated = [];

    /**
     * Errors by field.
     *
     * @var array<string, string[]>
     */
    public private(set) array $errors = [];

    /**
     * Messages by rule or `field:rule`.
     *
     * @var array<string, string>
     */
    private array $messages;

    /**
     * Custom rules by name or `field:rule`.
     *
     * @var array<string, callable>
     */
    private array $extensions = [];

    public function __construct(

        /**
         * Incoming fields and their values.
         */
        private array $fields = [],

        /**
         * Rules by field: `'required|lengthMin:2'`.
         *
         * @var array<string, string>
         */
        private array $rules = [],

        /**
         * Stop at the first rule of a field.
         */
        private bool $break = false,
    ) {
        $this->messages = self::$translated ?: self::translateMessages();
    }

    /**
     * Set the translation of the error messages; without it messages stay in English.
     *
     * @param Closure|null $translate Gets a message and the arguments of its `:name` placeholders, returns a string.
     * @return void
     */
    public static function configure(?Closure $translate = null): void
    {
        self::$translate  = $translate;
        self::$translated = [];
    }

    /**
     * Create a validator for the data, the facade entry point.
     *
     * @param array                 $fields
     * @param array<string, string> $rules
     * @param bool                  $break Stop at the first rule of a field.
     * @return Validator
     */
    public function data(array $fields, array $rules, bool $break = false): self
    {
        return new self($fields, $rules, $break);
    }

    /**
     * Check every field against its rules, errors go to $errors.
     *
     * @return Validator
     */
    public function apply(): self
    {
        foreach ($this->rules as $field => $rules) {
            $rules = explode('|', $rules);

            foreach ($rules as $rule) {
                [ $method, $comparisonValue ] = explode(':', $rule, 2) + [ null, null ];

                $value      = $this->fields[ $field ] ?? '';
                $comparison = $comparisonValue !== null ? ($this->fields[ $comparisonValue ] ?? $comparisonValue) : null;

                // a comma-separated argument is a list
                $comparisonValue_array = explode(',', $comparisonValue ?? '');
                if (count($comparisonValue_array) > 1) {
                    $comparison       = $comparisonValue_array;
                    $comparisonValue = implode(', ', $comparison);
                }

                $key = sprintf('%s:%s', $field, $method);
                if (method_exists($this, $method)) {
                    $error = call_user_func([ $this, $method ], $value, $comparison);
                } else {
                    $function = $this->extensions[ $method ] ?? ( $this->extensions[ $key ] ?? null );
                    if (is_callable($function)) {
                        $error = call_user_func($function, $this, $value, $comparison, $field);
                    }
                }

                $message = $this->messages[ $key ] ?? ( $this->messages[ $method ] ?? '' );
                if (isset($error) && ! $error && $message) {
                    $this->errors[ $field ][] = sprintf($message, $comparisonValue);
                }

                if ($this->break) {
                    continue( 2 );
                }
            }
        }

        return $this;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * Add a custom rule or replace the message of a rule.
     *
     * @param string        $type     Rule name or `field:rule` for one field.
     * @param string        $message  Message with `%s` for the rule argument.
     * @param callable|null $callback Gets the validator, the value, the argument and the field name, returns bool.
     * @return Validator
     */
    public function extend(string $type, string $message, ?callable $callback = null): self
    {
        $this->messages[ $type ] = $message;
        if ($callback !== null) {
            $this->extensions[ $type ] = $callback;
        }
        return $this;
    }

    /**
     * Translate MESSAGES with the configured callback, without it only fill the placeholders.
     *
     * @return array<string, string>
     */
    private static function translateMessages(): array
    {
        $translate = self::$translate ?? static fn (string $message, string ...$args): string => $args
            ? preg_replace_callback('/:\w+/', static function () use (&$args) {
                return array_shift($args);
            }, $message)
            : $message;

        foreach (self::MESSAGES as $rule => $message) {
            self::$translated[$rule] = $translate(...$message);
        }

        return self::$translated;
    }

    /**
     * Validate that a field was "accepted" (based on PHP's string evaluation rules)
     *
     * This validation rule implies the field is "required"
     *
     * @param mixed $value
     * @return bool
     */
    private function accepted(mixed $value): bool
    {
        return in_array($value, [ 'yes', 'on', 1, '1', true ], true);
    }

    /**
     * Validate that a field contains only alphabetic characters
     *
     * @param  string $value
     * @return bool
     */
    private function alpha(string $value): bool
    {
        return preg_match('/^([a-z])+$/i', $value) === 1;
    }

    /**
     * Validate that a field contains only alphanumeric characters
     *
     * @param string|int $value
     * @return bool
     */
    private function alphanumeric(string|int $value): bool
    {
        return preg_match('/^([a-z0-9])+$/i', (string) $value) === 1;
    }

    /**
     * Validate that a value is color in hex format
     *
     * @param string $value
     * @return bool
     */
    private function hex(string $value): bool
    {
        return preg_match('/^#([a-fA-F0-9]{3}){1,2}$/', $value) === 1;
    }

    /**
     * Validate that a value is color in hsl format
     *
     * @param string $value
     * @return bool
     */
    private function hsl(string $value): bool
    {
        return preg_match('/^hsl\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*\)$/', $value) === 1;
    }

    /**
     * Validate that a value is color in hsla format
     *
     * @param string $value
     * @return bool
     */
    private function hsla(string $value): bool
    {
        return preg_match('/^hsla\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*,\s*(0(\.\d+)?|1(\.0)?)\s*\)$/', $value) === 1;
    }

    /**
     * Validate that a value is color in rgb format
     *
     * @param string $value
     * @return bool
     */
    private function rgb(string $value): bool
    {
        return preg_match('/^(rgb)\(([01]?\d\d?|2[0-4]\d|25[0-5])(\W+)([01]?\d\d?|2[0-4]\d|25[0-5])\W+(([01]?\d\d?|2[0-4]\d|25[0-5])\))$/i', $value) === 1;
    }

    /**
     * Validate that a value is color in rgba format
     *
     * @param string $value
     * @return bool
     */
    private function rgba(string $value): bool
    {
        return preg_match('/^(rgba)\(([01]?\d\d?|2[0-4]\d|25[0-5])\W+([01]?\d\d?|2[0-4]\d|25[0-5])\W+([01]?\d\d?|2[0-4]\d|25[0-5])\)?\W+([01](\.\d+)?)\)$/i', $value) === 1;
    }

    /**
     * Validate that a field is a valid date
     *
     * @param  mixed $value
     * @return bool
     */
    private function date(mixed $value): bool
    {
        return $value instanceof DateTime || strtotime($value) !== false;
    }

    /**
     * Validate the date is after a given date
     *
     * @param mixed $value
     * @param string|DateTime $compare
     * @return bool
     */
    private function later(string|DateTime $value, string|DateTime $compare): bool
    {
        $vtime = ( $value instanceof DateTime ) ? $value->getTimestamp() : strtotime($value);
        $ptime = ( $compare instanceof DateTime ) ? $compare->getTimestamp() : strtotime($compare);
        return ( $vtime && $ptime ) && ( $vtime > $ptime );
    }

    /**
     * Validate the date is before a given date
     *
     * @param mixed $value
     * @param string|DateTime $compare
     * @return bool
     */
    private function earlier(string|DateTime $value, string|DateTime $compare): bool
    {
        $vtime = ( $value instanceof DateTime ) ? $value->getTimestamp() : strtotime($value);
        $ptime = ( $compare instanceof DateTime ) ? $compare->getTimestamp() : strtotime($compare);
        return ( $vtime && $ptime ) && ( $vtime < $ptime );
    }

    private function different(mixed $value): bool
    {
        return $value instanceof DateTime || strtotime($value) !== false;
    }

    /**
     * Validate that a field is a valid e-mail address
     *
     * @param  string $value
     * @return bool
     */
    private function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate that two values match
     *
     * @param int|string $value
     * @param int|string $comparisonValue
     * @return bool
     */
    private function equals(int|string $value, int|string $comparisonValue): bool
    {
        if (is_string($value)) {
            return $value === strval($comparisonValue);
        }
        return $value === intval($comparisonValue);
    }

    /**
     * Validate that a field is a valid IP address
     *
     * @param string $value
     * @return bool
     */
    private function ip(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate that a field is a valid IP v4 address
     *
     * @param string $value
     * @return bool
     */
    private function ipv4(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate that a field is a valid IP v6 address
     *
     * @param string $value
     * @return bool
     */
    private function ipv6(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validate the length of a string
     *
     * @param mixed $value
     * @param mixed $comparisonValue
     * @return bool
     */
    private function length(mixed $value, mixed $comparisonValue): bool
    {
        return mb_strlen($value) === intval($comparisonValue);
    }

    /**
     * Validate the length of a string (min)
     *
     * @param mixed $value
     * @param mixed $comparisonValue
     * @return bool
     */
    private function lengthMin(mixed $value, mixed $comparisonValue): bool
    {
        return mb_strlen($value) >= intval($comparisonValue);
    }

    /**
     * Validate the length of a string (max)
     *
     * @param mixed $value
     * @param mixed $comparisonValue
     * @return bool
     */
    private function lengthMax(mixed $value, mixed $comparisonValue): bool
    {
        return mb_strlen($value) <= intval($comparisonValue);
    }

    /**
     * Validate MAC address
     *
     * @param string $value
     * @return bool
     */
    private function mac(string $value): bool
    {
        return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value) === 1;
    }

    /**
     * Validate the value is less than a maximum value
     *
     * @param mixed $value
     * @param mixed $maximumValue
     * @return bool
     */
    private function max(mixed $value, mixed $maximumValue): bool
    {
        if (function_exists('bccomp')) {
            $value        = strval($value);
            $maximumValue = strval($maximumValue);

            return ! ( bccomp($value, $maximumValue, 14) === 1 );
        }
        return $maximumValue <= $value;
    }

    /**
     * Validate the value is greater than a minimum value.
     *
     * @param mixed $value
     * @param mixed $minimumValue
     * @return bool
     */
    private function min(mixed $value, mixed $minimumValue): bool
    {
        if (function_exists('bccomp')) {
            $value        = strval($value);
            $minimumValue = strval($minimumValue);

            return ! ( bccomp($minimumValue, $value, 14) >= 0 );
        }
        return $minimumValue >= $value;
    }

    /**
     * Validate that a value is numeric
     *
     * @param mixed $value
     * @return bool
     */
    private function numeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Required field validator
     *
     * @param mixed $value
     * @return bool
     */
    private function required(mixed $value): bool
    {
        return is_scalar($value) && ! empty($value);
    }

    /**
     * Validate that a field passes a regular expression check
     *
     * @param mixed $value
     * @param mixed $regexp
     * @return bool
     */
    private function regex(mixed $value, mixed $regexp): bool
    {
        return preg_match((string) $regexp, (string) $value) === 1;
    }

    /**
     * Validate that a field value same as value of other field
     *
     * @param mixed $value
     * @param mixed $comparisonValue
     * @return bool
     */
    private function similar(mixed $value, mixed $comparisonValue): bool
    {
        return $value === $comparisonValue;
    }

    /**
     * Validate that a field contains only alphanumeric characters, dashes, and underscores
     *
     * @param  $value
     * @return bool
     */
    private function slug($value): bool
    {
        if (is_array($value)) {
            return false;
        }

        $value = (string) $value;

        return ! str_contains($value, '/') && preg_match('/^([-a-z0-9_-])+$/i', $value) === 1;
    }

    /**
     * Validate tld
     *
     * @param  string $value
     * @return bool
     */
    private function tld(string $value): bool
    {
        return preg_match('/^[a-zA-Z]{2,}$/i', $value) && str_ends_with($value, '.') === false;
    }

    /**
     * Validate that a field is a valid URL by syntax
     *
     * @param  string $value
     * @return bool
     */
    private function url(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Validate UUID
     *
     * @param  string $value
     * @return bool
     */
    private function uuid(string $value): bool
    {
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $value) === 1;
    }

    /**
     * Checking the value for compliance from the list
     *
     * @param string $value
     * @param array $data
     * @return bool
     */
    private function in(string $value, array $data): bool
    {
        return in_array($value, $data, true);
    }

    /**
     * Checking the value for absence from the list
     *
     * @param string $value
     * @param array $data
     * @return bool
     */
    private function notIn(string $value, array $data): bool
    {
        return ! $this->in($value, $data);
    }

    /**
     * Validate file mime-type
     *
     * @param string $value
     * @param array|string $types
     * @return bool
     */
    private function type(string $value, array|string $types): bool
    {
        return $this->in($value, (array) $types);
    }

    /**
     * Validate file extension
     *
     * @param string $value
     * @param array|string $comparisonValue
     * @return bool
     */
    private function extension(string $value, array|string $comparisonValue): bool
    {
        return $this->in(pathinfo($value, PATHINFO_EXTENSION), (array) $comparisonValue);
    }

    /**
     * Validate file minimum size
     *
     * @param mixed $value
     * @param int|string $comparisonValue
     * @return bool
     */
    private function minSize(mixed $value, int|string $comparisonValue): bool
    {
        $units = [ 'b', 'kb', 'mb', 'gb' ];
        if (is_string($comparisonValue)) {
            $comparisonValue = (int) $comparisonValue * pow(1024, array_search(strtolower(substr($comparisonValue, -2)), $units, true));
        }
        return intval($value) <= intval($comparisonValue);
    }

    /**
     * Validate file maximum size
     *
     * @param int $value
     * @param int|string $comparisonValue
     * @return bool
     */
    private function maxSize(mixed $value, int|string $comparisonValue): bool
    {
        $units = [ 'b', 'kb', 'mb', 'gb' ];
        if (is_string($comparisonValue)) {
            $comparisonValue = (int) $comparisonValue * pow(1024, array_search(strtolower(substr($comparisonValue, -2)), $units, true));
        }
        return intval($value) >= intval($comparisonValue);
    }
}
