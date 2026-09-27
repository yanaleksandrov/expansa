<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Contracts\Engine;

/**
 * Returns the file content as is: HTML and CSS templates.
 *
 * @package Expansa\View
 */
final class File implements Engine
{
    public private(set) string $lastRendered = '';

    public function render(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        return file_get_contents($path);
    }
}
