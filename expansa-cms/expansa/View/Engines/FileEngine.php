<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

class FileEngine extends Engine
{
    #[\Override]
    public function get(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        return file_get_contents($path);
    }
}
