<?php

declare(strict_types=1);

namespace Expansa\Security;

use DateTime;

/**
 * Validates input against certain criteria.
 *
    print_r(
        Validator::data(
            $_POST,
            [
                'field1'  => 'accepted',
                'field2'  => 'alpha',
                'field3'  => 'alphanumeric',
                'field4'  => 'hex',
                'field5'  => 'hsl',
                'field6'  => 'hsla',
                'field7'  => 'rgb',
                'field8'  => 'rgba',
                'field9'  => 'date',
                'field10' => 'later:field12',
                'field11' => 'earlier:field12',
                'field12' => 'different:field1',
                'field13' => 'email',
                'field14' => 'equals:45',
                'field15' => 'ip',
                'field16' => 'ipv4',
                'field17' => 'ipv6',
                'field18' => 'length:4',
                'field19' => 'lengthMin:2',
                'field20' => 'lengthMax:6',
                'field21' => 'mac',
                'field22' => 'max:14',
                'field23' => 'min:10',
                'field24' => 'numeric',
                'field25' => 'required',
                'field26' => 'similar:field1',
                'field27' => 'slug',
                'field28' => 'tld',
                'field29' => 'url',
                'field30' => 'uuid',

                'field31' => 'time:H:i:s',
                'field32' => 'required|numeric',
            ]
        )->extend(
            'time',
            t( 'Time must be in \'%s\' format' ),
            function( $validator, $value, $comparisonValue ) {
                $time = DateTime::createFromFormat( $comparisonValue, $value );

                return $time && $time->format( $comparisonValue ) === $value;
            }
        )->extend(
            'array',
            t( 'This is not array' ),
            function( $validator, $value ) {
                return is_array( $value );
            }
        )->extend(
            'numeric',
            t( 'Oh no, must be numeric' )
        )->extend(
            'field32:numeric',
            t( 'Message for specified field: must be numeric' )
        )->apply()
    );
 *
 * @package Expansa
 */
final class Validator
{
    /**
     * Errors messages list
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * List for custom rules for extend validation
     *
     * @var array
     */
    protected array $extensions = [];

    /**
     * Errors list for return
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Setup validation
     *
     * @param array $fields Incoming fields and their values.
     * @param array $rules  Validation rules list.
     * @param bool  $break  Flag to stop validation if the first error is found.
     */
    public function __construct(
        protected array $fields = [],
        protected array $rules = [],
        protected bool $break = false
    )
    {
        $this->messages = [
            'accepted'     => t('Must be accepted.'),
            'alpha'        => t('Must contain only letters.'),
            'alphanumeric' => t('Must contain only letters and/or numbers.'),
            'hex'          => t('The color format should be :format.', 'HEX'),
            'hsl'          => t('The color format should be :format.', 'HSL'),
            'hsla'         => t('The color format should be :format.', 'HSLA'),
            'rgb'          => t('The color format should be :format.', 'RGB'),
            'rgba'         => t('The color format should be :format.', 'RGBA'),
            'date'         => t('Is not a valid date.'),
            'later'        => t("Must be date after '%s'."),
            'earlier'      => t("Must be date before '%s'."),
            'different'    => t("Must be different than '%s'."),
            'email'        => t('Is not a valid email address.'),
            'equals'       => t("Must be the same as '%s'."),
            'ip'           => t('Is not a valid IP address.'),
            'ipv4'         => t('Is not a valid IPv4 address.'),
            'ipv6'         => t('Is not a valid IPv6 address.'),
            'length'       => t('Must be %d characters long.'),
            'lengthMin'    => t('Must be at least %d characters long.'),
            'lengthMax'    => t('Must not exceed %d characters.'),
            'mac'          => t('Is not a valid MAC address.'),
            'max'          => t('Must be no more than %s.'),
            'min'          => t('Must be at least %s.'),
            'numeric'      => t('Must be numeric.'),
            'required'     => t('Is required.'),
            'regex'        => t('The field is not valid format.'),
            'similar'      => t("Value of this field must be same with '%s'."),
            'slug'         => t('Must contain only letters, numbers, dashes and underscores.'),
            'tld'          => t('Is not a valid top-level domain (TLD).'),
            'url'          => t('Is not a valid URL.'),
            'uuid'         => t('Is not a valid UUID.'),
            'type'         => t('This type of file is not allowed.'),
            'minSize'      => t('File size is too small. Must be greater than or equal to %s.'),
            'maxSize'      => t('File size is too big. Must be less than %s.'),
            'extension'    => t('Invalid file extension. Accepted extensions are: %s.'),
            ...$this->messages,
        ];
    }

    /**
     * Setup validator rules via `data` method.
     *
     * @param array $fields
     * @param array $rules
     * @param bool  $break
     * @return Validator
     */
    public function data(array $fields, array $rules, bool $break = false): self
    {
        return new self($fields, $rules, $break);
    }

    /**
     * Apply validation
     *
     * @return Validator
     */
    public function apply(): self
    {
        foreach ($this->rules as $field => $rules) {
            $rules = explode('|', $rules);

            foreach ($rules as $rule) {
                [ $method, $comparisonValue ] = explode(':', $rule, 2) + [ null, null ];

                // checking the value for compliance with the condition
                $value      = $this->fields[ $field ] ?? '';
                $comparison = $this->fields[ $comparisonValue ] ?? $comparisonValue;

                // check if $comparisonValue is a list of data
                $comparisonValue_array = explode(',', $comparisonValue ?? '');
                if (count($comparisonValue_array) > 1) {
                    $comparison       = $comparisonValue_array;
                    $comparisonValue = implode(', ', $comparison);
                }

                // run class methods
                $key = sprintf('%s:%s', $field, $method);
                if (method_exists($this, $method)) {
                    $error = call_user_func([ $this, $method ], $value, $comparison);
                } else {
                    $function = $this->extensions[ $method ] ?? ( $this->extensions[ $key ] ?? null );
                    if (is_callable($function)) {
                        $error = call_user_func($function, $this, $value, $comparison, $field);
                    }
                }

                // fetch error message
                $message = $this->messages[ $key ] ?? ( $this->messages[ $method ] ?? '' );
                if (isset($error) && ! $error && $message) {
                    $this->errors[ $field ][] = sprintf($message, $comparisonValue);
                }

                // if option is active, skip other errors
                if ($this->break) {
                    continue( 2 );
                }
            }
        }

        return $this;
    }

    /**
     * Check data is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check data is invalid.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add custom validation rule or override messages
     *
     * @param string $type
     * @param string $message
     * @param callable|null $callback
     * @return Validator
     */
    public function extend(string $type, string $message, ?callable $callback = null): self
    {
        $this->messages[ $type ] = $message;
        if (is_callable($callback)) {
            $this->extensions[ $type ] = $callback;
        }
        return $this;
    }

    /**
     * Validate that a field was "accepted" (based on PHP's string evaluation rules)
     *
     * This validation rule implies the field is "required"
     *
     * @param mixed $value
     * @return bool
     */
    protected function accepted(mixed $value): bool
    {
        return in_array($value, [ 'yes', 'on', 1, '1', true ], true);
    }

    /**
     * Validate that a field contains only alphabetic characters
     *
     * @param  string $value
     * @return bool
     */
    protected function alpha(string $value): bool
    {
        return preg_match('/^([a-z])+$/i', $value);
    }

    /**
     * Validate that a field contains only alphanumeric characters
     *
     * @param string|int $value
     * @return bool
     */
    protected function alphanumeric(string|int $value): bool
    {
        return preg_match('/^([a-z0-9])+$/i', $value);
    }

    /**
     * Validate that a value is color in hex format
     *
     * @param string $value
     * @return bool
     */
    protected function hex(string $value): bool
    {
        return preg_match('/^#([a-fA-F0-9]{3}){1,2}$/', $value) === 1;
    }

    /**
     * Validate that a value is color in hsl format
     *
     * @param string $value
     * @return bool
     */
    protected function hsl(string $value): bool
    {
        return preg_match('/^hsl\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*\)$/', $value) === 1;
    }

    /**
     * Validate that a value is color in hsla format
     *
     * @param string $value
     * @return bool
     */
    protected function hsla(string $value): bool
    {
        return preg_match('/^hsla\(\s*\d+\s*,\s*\d+%?\s*,\s*\d+%?\s*,\s*(0(\.\d+)?|1(\.0)?)\s*\)$/', $value) === 1;
    }

    /**
     * Validate that a value is color in rgb format
     *
     * @param string $value
     * @return bool
     */
    protected function rgb(string $value): bool
    {
        return preg_match('/^(rgb)\(([01]?\d\d?|2[0-4]\d|25[0-5])(\W+)([01]?\d\d?|2[0-4]\d|25[0-5])\W+(([01]?\d\d?|2[0-4]\d|25[0-5])\))$/i', $value) === 1;
    }

    /**
     * Validate that a value is color in rgba format
     *
     * @param string $value
     * @return bool
     */
    protected function rgba(string $value): bool
    {
        return preg_match('/^(rgba)\(([01]?\d\d?|2[0-4]\d|25[0-5])\W+([01]?\d\d?|2[0-4]\d|25[0-5])\W+([01]?\d\d?|2[0-4]\d|25[0-5])\)?\W+([01](\.\d+)?)\)$/i', $value) === 1;
    }

    /**
     * Validate that a field is a valid date
     *
     * @param  mixed $value
     * @return bool
     */
    protected function date(mixed $value): bool
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
    protected function later(string|DateTime $value, string|DateTime $compare): bool
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
    protected function earlier(string|DateTime $value, string|DateTime $compare): bool
    {
        $vtime = ( $value instanceof DateTime ) ? $value->getTimestamp() : strtotime($value);
        $ptime = ( $compare instanceof DateTime ) ? $compare->getTimestamp() : strtotime($compare);
        return ( $vtime && $ptime ) && ( $vtime < $ptime );
    }

    protected function different(mixed $value): bool
    {
        return $value instanceof DateTime || strtotime($value) !== false;
    }

    /**
     * Validate that a field is a valid e-mail address
     *
     * @param  string $value
     * @return bool
     */
    protected function email(string $value): bool
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
    protected function equals(int|string $value, int|string $comparisonValue): bool
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
    protected function ip(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate that a field is a valid IP v4 address
     *
     * @param string $value
     * @return bool
     */
    protected function ipv4(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate that a field is a valid IP v6 address
     *
     * @param string $value
     * @return bool
     */
    protected function ipv6(string $value): bool
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
    protected function length(mixed $value, mixed $comparisonValue): bool
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
    protected function lengthMin(mixed $value, mixed $comparisonValue): bool
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
    protected function lengthMax(mixed $value, mixed $comparisonValue): bool
    {
        return mb_strlen($value) <= intval($comparisonValue);
    }

    /**
     * Validate MAC address
     *
     * @param string $value
     * @return bool
     */
    protected function mac(string $value): bool
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
    protected function max(mixed $value, mixed $maximumValue): bool
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
    protected function min(mixed $value, mixed $minimumValue): bool
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
    protected function numeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Required field validator
     *
     * @param mixed $value
     * @return bool
     */
    protected function required(mixed $value): bool
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
    protected function regex(mixed $value, mixed $regexp): bool
    {
        return preg_match($regexp, $value);
    }

    /**
     * Validate that a field value same as value of other field
     *
     * @param mixed $value
     * @param mixed $comparisonValue
     * @return bool
     */
    protected function similar(mixed $value, mixed $comparisonValue): bool
    {
        return $value === $comparisonValue;
    }

    /**
     * Validate that a field contains only alphanumeric characters, dashes, and underscores
     *
     * @param  $value
     * @return bool
     */
    protected function slug($value): bool
    {
        return ! is_array($value) && str_contains($value, '/') ? false : preg_match('/^([-a-z0-9_-])+$/i', $value);
    }

    /**
     * Validate tld
     *
     * @param  string $value
     * @return bool
     */
    protected function tld(string $value): bool
    {
        return preg_match('/^[a-zA-Z]{2,}$/i', $value) && str_ends_with($value, '.') === false;
    }

    /**
     * Validate that a field is a valid URL by syntax
     *
     * @param  string $value
     * @return bool
     */
    protected function url(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Validate UUID
     *
     * @param  string $value
     * @return bool
     */
    protected function uuid(string $value): bool
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
    protected function in(string $value, array $data): bool
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
    protected function notIn(string $value, array $data): bool
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
    protected function type(string $value, array|string $types): bool
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
    protected function extension(string $value, array|string $comparisonValue): bool
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
    protected function minSize(mixed $value, int|string $comparisonValue): bool
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
    protected function maxSize(mixed $value, int|string $comparisonValue): bool
    {
        $units = [ 'b', 'kb', 'mb', 'gb' ];
        if (is_string($comparisonValue)) {
            $comparisonValue = (int) $comparisonValue * pow(1024, array_search(strtolower(substr($comparisonValue, -2)), $units, true));
        }
        return intval($value) >= intval($comparisonValue);
    }
}
