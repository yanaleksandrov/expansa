<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Contracts;

use Expansa\Filesystem\Directory;
use Expansa\Filesystem\File;

interface CommonInterface
{
    public function clean(): Directory|File;

    public function copy(string $name): Directory|File;

    public function move(string $to): Directory|File;

    public function rename(string $name): Directory|File;

    public function get(string $path): Directory|File;

    public function delete(): bool;

    public function download(): void;
}
