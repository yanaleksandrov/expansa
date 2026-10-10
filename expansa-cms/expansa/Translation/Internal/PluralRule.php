<?php

declare(strict_types=1);

namespace Expansa\Translation\Internal;

use Closure;
use InvalidArgumentException;

/**
 * Compiles a gettext plural expression (`n%10==1 && n%100!=11 ? 0 : 1`) into a closure returning the form index.
 * The expression comes from the language list, which plugins extend, so it is parsed rather than evaluated:
 * only `n`, integers, parentheses, `?:` and the arithmetic, comparison and logical operators are accepted.
 *
 * @internal
 * @package Expansa\Translation\Internal
 */
final class PluralRule
{
    /**
     * Counts whose form index a compiled rule remembers.
     */
    private const int CACHE_SIZE = 1000;

    /**
     * Tokens of the expression being compiled.
     *
     * @var list<string>
     */
    private array $tokens = [];

    private int $position = 0;

    /**
     * Compile an expression.
     *
     * @param string $expression
     * @return Closure fn (int $n): int
     * @throws InvalidArgumentException If the expression has other tokens or is malformed.
     */
    public static function compile(string $expression): Closure
    {
        $rule = new self();

        preg_match_all('/\s*(\d+|n|\|\||&&|==|!=|<=|>=|[<>?:()%+\-*\/!])\s*/A', $expression, $matches);

        // the tokens must cover the whole expression, anything else is rejected
        if (implode('', $matches[0]) !== $expression) {
            throw new InvalidArgumentException("Plural rule \"$expression\" has unsupported tokens.");
        }

        $rule->tokens = $matches[1];
        $node         = $rule->ternary();

        if ($rule->position !== count($rule->tokens)) {
            throw new InvalidArgumentException("Plural rule \"$expression\" is malformed.");
        }

        // a page repeats the same counts, and the closure tree of a long rule costs about a microsecond
        $cache = [];

        return function (int $n) use ($node, &$cache): int {
            if (isset($cache[$n])) {
                return $cache[$n];
            }

            $index = (int) $node($n);
            if (count($cache) < self::CACHE_SIZE) {
                $cache[$n] = $index;
            }

            return $index;
        };
    }

    /**
     * Ternary `condition ? then : else`, right-associative, the lowest precedence.
     */
    private function ternary(): Closure
    {
        $condition = $this->binary(0);

        if (! $this->accept('?')) {
            return $condition;
        }

        $then = $this->ternary();
        $this->expect(':');
        $else = $this->ternary();

        return static fn (int $n): int|bool => $condition($n) ? $then($n) : $else($n);
    }

    /**
     * Binary operators by precedence level, from `||` to `*`.
     *
     * @param int $level Index in the operator levels.
     */
    private function binary(int $level): Closure
    {
        static $levels = [['||'], ['&&'], ['==', '!='], ['<', '>', '<=', '>='], ['+', '-'], ['*', '/', '%']];

        if ($level === count($levels)) {
            return $this->unary();
        }

        $left = $this->binary($level + 1);

        while (in_array($this->tokens[$this->position] ?? null, $levels[$level], true)) {
            $operator = $this->tokens[$this->position++];
            $right    = $this->binary($level + 1);
            $left     = self::operation($operator, $left, $right);
        }

        return $left;
    }

    private function unary(): Closure
    {
        if ($this->accept('!')) {
            $operand = $this->unary();

            return static fn (int $n): bool => ! $operand($n);
        }

        if ($this->accept('-')) {
            $operand = $this->unary();

            return static fn (int $n): int => -$operand($n);
        }

        return $this->primary();
    }

    private function primary(): Closure
    {
        $token = $this->tokens[$this->position++] ?? '';

        if ($token === 'n') {
            return static fn (int $n): int => $n;
        }

        if (ctype_digit($token)) {
            $value = (int) $token;

            return static fn (): int => $value;
        }

        if ($token === '(') {
            $node = $this->ternary();
            $this->expect(')');

            return $node;
        }

        throw new InvalidArgumentException("Unexpected token \"$token\" in a plural rule.");
    }

    /**
     * Closure of a binary operation; division by zero gives 0, as a rule never divides by zero on purpose.
     */
    private static function operation(string $operator, Closure $left, Closure $right): Closure
    {
        return match ($operator) {
            '||' => fn (int $n): bool => $left($n) || $right($n),
            '&&' => fn (int $n): bool => $left($n) && $right($n),
            '==' => fn (int $n): bool => $left($n) == $right($n),
            '!=' => fn (int $n): bool => $left($n) != $right($n),
            '<'  => fn (int $n): bool => $left($n) < $right($n),
            '>'  => fn (int $n): bool => $left($n) > $right($n),
            '<=' => fn (int $n): bool => $left($n) <= $right($n),
            '>=' => fn (int $n): bool => $left($n) >= $right($n),
            '+'  => fn (int $n): int => (int) $left($n) + (int) $right($n),
            '-'  => fn (int $n): int => (int) $left($n) - (int) $right($n),
            '*'  => fn (int $n): int => (int) $left($n) * (int) $right($n),
            '/'  => fn (int $n): int => ($divisor = (int) $right($n)) === 0 ? 0 : intdiv((int) $left($n), $divisor),
            '%'  => fn (int $n): int => ($divisor = (int) $right($n)) === 0 ? 0 : (int) $left($n) % $divisor,
        };
    }

    private function accept(string $token): bool
    {
        if (($this->tokens[$this->position] ?? null) !== $token) {
            return false;
        }

        $this->position++;

        return true;
    }

    private function expect(string $token): void
    {
        if (! $this->accept($token)) {
            throw new InvalidArgumentException("Expected \"$token\" in a plural rule.");
        }
    }
}
