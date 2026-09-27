<?php

declare(strict_types=1);

namespace Expansa\Ai\Contracts;

use Expansa\Ai\Exceptions\UnknownTool;

interface ToolRegistry
{
    /**
     * Lists every capability available to the AI.
     * The returned tools can be described or run by name.
     *
     * @return Tool[] Registered capability implementations
     */
    public function all(): array;

    /**
     * Runs a registered capability by its unique name.
     *
     * @param string $name Registered capability name
     * @param array<string, mixed> $arguments Tool-specific input values
     * @return string Capability result
     * @throws UnknownTool When the name is not registered
     */
    public function handle(string $name, array $arguments): string;
}
