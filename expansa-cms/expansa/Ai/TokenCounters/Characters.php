<?php

declare(strict_types=1);

namespace Expansa\Ai\TokenCounters;

use Expansa\Ai\Contracts\TokenCounter;

/**
 * Conservative fallback counter that treats each Unicode character as a token.
 * Applications should provide the tokenizer matching their selected AI model.
 */
final class Characters implements TokenCounter
{
    /**
     * Counts Unicode code points as a conservative token estimate.
     * Invalid UTF-8 bytes are counted one by one, so they never shrink the result.
     *
     * @param string $text Text to measure
     * @return int Number of Unicode code points
     */
    public function count(string $text): int
    {
        return mb_strlen($text, 'UTF-8');
    }

    /**
     * Truncates text to the configured Unicode code point count.
     *
     * @param string $text Text to truncate
     * @param int $maxTokens Maximum number of code points to retain
     * @return string Truncated text
     */
    public function truncate(string $text, int $maxTokens): string
    {
        return $maxTokens > 0 ? mb_substr($text, 0, $maxTokens, 'UTF-8') : '';
    }
}
