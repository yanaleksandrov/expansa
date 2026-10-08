<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

interface TokenCounter
{
    /**
     * Counts tokens using the tokenizer configured for the selected model.
     * The result is used for request, context, and tool-result limits.
     *
     * @param string $text Text to measure
     * @return int Number of tokens in the text
     */
    public function count(string $text): int;

    /**
     * Truncates text without exceeding the supplied token limit.
     * Implementations should preserve valid text encoding.
     *
     * @param string $text Text to truncate
     * @param int $maxTokens Maximum number of tokens to retain
     * @return string Truncated text
     */
    public function truncate(string $text, int $maxTokens): string;
}
