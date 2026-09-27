<?php

declare(strict_types=1);

namespace Expansa\Ai\Tools;

use Expansa\Ai\Contracts\Tool;
use Expansa\Ai\Contracts\ToolRegistry;
use Expansa\Ai\Exceptions\UnknownTool;

/**
 * Stores and dispatches the tools available to extension generation.
 */
final class Registry implements ToolRegistry
{
    /**
     * Registered tools keyed by their unique names.
     *
     * @var array<string, Tool>
     */
    private array $tools = [];

    /**
     * Registers supplied capabilities by their unique names.
     * Later tools with the same name replace earlier registrations.
     *
     * @param Tool[] $tools Capability implementations to register
     */
    public function __construct(array $tools = [])
    {
        foreach ($tools as $tool) {
            $this->tools[$tool->name] = $tool;
        }
    }

    /**
     * Returns the registered capabilities in insertion order.
     *
     * @return Tool[] Registered tool implementations
     */
    public function all(): array
    {
        return array_values($this->tools);
    }

    /**
     * Runs one capability with its input values.
     *
     * @param string $name Registered tool name
     * @param array<string, mixed> $arguments Tool-specific input values
     * @return string Tool result
     * @throws UnknownTool When the name is not registered
     */
    public function handle(string $name, array $arguments): string
    {
        if (! isset($this->tools[$name])) {
            throw new UnknownTool("Unknown AI tool: $name");
        }

        return $this->tools[$name]->handle($arguments);
    }
}
