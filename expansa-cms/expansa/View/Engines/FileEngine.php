<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

class FileEngine extends Engine
{
    public function get(string $path): string
    {
        $this->lastRendered = $path;

        return file_get_contents($path);
    }
}
