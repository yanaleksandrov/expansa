<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

interface Context
{
    /**
     * Returns relevant reference material for this user input.
     * Implementations must respect the supplied token limit.
     *
     * @param string $input User requirements and prior clarifications
     * @param int $maxTokens Maximum returned context token count
     * @return string Relevant context supplied to the AI
     */
    public function get(string $input, int $maxTokens): string;
}
