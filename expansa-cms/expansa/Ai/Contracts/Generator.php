<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

use Expansa\Ai\Brief;
use Expansa\Ai\Exceptions\InvalidResponse;
use Expansa\Ai\Generation;

interface Generator
{
    /**
     * Produces an extension file map from the brief.
     * Repair attempts receive the previous files and their validation errors.
     *
     * @param Brief $brief Request, context, specification, and previous attempt
     * @param int $maxOutputTokens Maximum tokens requested from the provider
     * @return Generation Files and generation provider usage
     * @throws InvalidResponse When the model output is not a file map; the manager treats it as repairable
     */
    public function generate(Brief $brief, int $maxOutputTokens): Generation;
}
