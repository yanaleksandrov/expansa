<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Internal\Compiler;

/**
 * Compiles a Blade template to PHP and renders the compiled file.
 * A template is checked once per process, repeated renders skip the file time checks.
 *
 * @package Expansa\View
 */
final class Blade extends Php
{
    /**
     * Compiled files by template.
     *
     * @var array<string, string>
     */
    private array $compiled = [];

    public function __construct(

        /**
         * Compiles templates, with a cache directory to reuse compiled files between requests.
         */
        private readonly Compiler $compiler = new Compiler(),
    ) {}

    #[\Override]
    public function render(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        return $this->evaluatePath($this->compiled[$path] ??= $this->compiler->compile($path), $data);
    }
}
