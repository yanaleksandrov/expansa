<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

use Expansa\Ai\Completion;
use Expansa\Ai\Prompt;

interface Provider
{
    /**
     * Sends a prompt to the configured language model.
     * Implementations should use structured output when the prompt has a schema.
     *
     * @param Prompt $prompt System instructions, request data, and response schema
     * @param int $maxOutputTokens Maximum requested response size
     * @return Completion Text, token counts, and provider metadata
     */
    public function complete(Prompt $prompt, int $maxOutputTokens): Completion;
}
