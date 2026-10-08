<?php

declare(strict_types=1);

namespace Expansa\Ai\Internal;

/**
 * PHP token helpers shared by the source validators.
 *
 * @internal
 */
final class Tokens
{
    /**
     * Tokens that make the following name a method, a declaration, or a class, not a global function call.
     */
    private const array NOT_A_CALL = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST];

    /**
     * Tokenizes source without whitespace and comments; single-character tokens use the character as id.
     *
     * @param string $source PHP source
     * @return array<int, array{int|string, string, int}> Token id, text, and line
     */
    public static function significant(string $source): array
    {
        $tokens = [];
        $line = 1;
        foreach (token_get_all($source) as $token) {
            [$id, $text] = is_array($token) ? $token : [$token, $token];
            $line = is_array($token) ? $token[2] : $line;
            if (! in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $tokens[] = [$id, $text, $line];
            }

            $line += substr_count($text, "\n");
        }

        return $tokens;
    }

    /**
     * Checks whether the token at the position calls a global function by name.
     *
     * @param array<int, array{int|string, string, int}> $tokens Significant tokens
     * @param int $index Position of the name token
     */
    public static function isFunctionCall(array $tokens, int $index): bool
    {
        return in_array($tokens[$index][0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)
            && ($tokens[$index + 1][0] ?? null) === '('
            && ! in_array($tokens[$index - 1][0] ?? null, self::NOT_A_CALL, true);
    }

    /**
     * Returns a global name in lower case without the leading backslash.
     *
     * @param string $name Name token text
     */
    public static function name(string $name): string
    {
        return strtolower(ltrim($name, '\\'));
    }
}
