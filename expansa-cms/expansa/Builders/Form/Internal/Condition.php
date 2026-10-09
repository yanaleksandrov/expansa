<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Internal;

use Expansa\Codecs\Json;
use Expansa\Security\Sanitizer;

/**
 * A condition on another field's value that shows or hides a field. The browser checks it
 * by the `u-show` expression, the server by matches() to hide the field before the scripts run.
 *
 * @internal
 * @package Expansa\Builders
 */
final class Condition
{
    public function __construct(

        /**
         * Name of the checked field.
         */
        public readonly string $field,

        /**
         * One of `>`, `>=`, `<`, `<=`, `==`, `===`, `!=`, `!==`, `contains`, `pattern`.
         */
        public readonly string $operator,

        /**
         * Value to compare with: a list for `contains`, a regex without delimiters or a list of them for `pattern`.
         */
        public readonly mixed $value,
    ) {}

    /**
     * Get the JS expression that is true while the field meets the condition.
     *
     * @return string
     */
    public function expression(): string
    {
        $prop     = Sanitizer::prop($this->field);
        $compare  = "$prop {$this->operator} {$this->literal()}";
        $includes = new Json()->encode($this->value) . ".includes($prop)";
        $patterns = new Json()->encode($this->patterns());

        return match ($this->operator) {
            '>', '>=', '<', '<=' => $compare,
            '==', '==='          => is_array($this->value) ? $includes : $compare,
            '!=', '!=='          => is_array($this->value) ? "!$includes" : $compare,
            'contains'           => $includes,
            'pattern'            => "$patterns.some(pattern => new RegExp(pattern, 'u').test($prop))",
        };
    }

    /**
     * Whether the field value meets the condition, the same check as expression() does in the browser.
     *
     * @param mixed $current Value of the checked field.
     * @return bool
     */
    public function matches(mixed $current): bool
    {
        return match ($this->operator) {
            '>'        => $current > $this->value,
            '>='       => $current >= $this->value,
            '<'        => $current < $this->value,
            '<='       => $current <= $this->value,
            '=='       => $current == $this->value,
            '==='      => $current === $this->value,
            '!='       => $current != $this->value,
            '!=='      => $current !== $this->value,
            'contains' => in_array($current, $this->value, true),
            'pattern'  => $this->matchesPattern($current),
        };
    }

    /**
     * Get the value as a JS literal.
     *
     * @return string
     */
    private function literal(): string
    {
        return match (gettype($this->value)) {
            'boolean'           => $this->value ? 'true' : 'false',
            'integer', 'double' => (string) $this->value,
            'array'             => new Json()->encode($this->value),
            default             => "'" . Sanitizer::attribute($this->value) . "'",
        };
    }

    /**
     * Get the patterns: the value is a single one or a list of them.
     *
     * @return string[]
     */
    private function patterns(): array
    {
        return (array) $this->value;
    }

    /**
     * Whether the value matches any pattern, as `new RegExp(pattern, 'u').test(value)` does in JS:
     * a boolean is tested as `true` or `false`.
     *
     * @param mixed $current
     * @return bool
     */
    private function matchesPattern(mixed $current): bool
    {
        if (! is_scalar($current)) {
            return false;
        }

        $subject = is_bool($current) ? ($current ? 'true' : 'false') : (string) $current;

        // \x01 is never part of a pattern, so it delimits any of them without escaping
        return array_any(
            $this->patterns(),
            fn (string $pattern) => preg_match("\x01" . $pattern . "\x01u", $subject) === 1,
        );
    }
}
