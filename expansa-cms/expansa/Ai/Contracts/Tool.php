<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

interface Tool
{
    /**
     * Unique name the AI uses when it requests a tool call.
     */
    public string $name { get; }

    /**
     * Concise usage guidance included in the analysis prompt.
     */
    public string $description { get; }

    /**
     * JSON Schema of the `arguments` object the tool accepts.
     *
     * @var array<string, mixed>
     */
    public array $parameters { get; }

    /**
     * Runs a capability requested during specification.
     * Exceptions are reported back to the model as the tool's error.
     *
     * @param array<string, mixed> $arguments Tool-specific input values
     * @return string Tool result for the model
     */
    public function handle(array $arguments): string;
}
