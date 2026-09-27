<?php

declare(strict_types=1);

namespace Expansa\View\Contracts;

/**
 * Renders a template file of one extension; the manager creates an engine once and reuses it.
 *
 * @package Expansa\View
 */
interface Engine
{
    /**
     * Template file rendered last, '' before the first render.
     */
    public string $lastRendered { get; }

    /**
     * Render the template with its variables.
     *
     * @param string               $path Template file.
     * @param array<string, mixed> $data Variables available in the template.
     * @return string
     */
    public function render(string $path, array $data = []): string;
}
