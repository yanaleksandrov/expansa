<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Contracts;

use Expansa\Filesystem\Directory;

interface DirectoryInterface
{
    public function chmod(int $mode = 0755, bool $recursive = false): Directory;

    public function read(int $depth = 0, bool $treeFormat = false): array;

    public function files(string $pattern = '*', int $depth = 0): array;

    public function make(int $mode = 0755): Directory;
}
