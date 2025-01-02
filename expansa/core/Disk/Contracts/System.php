<?php

declare(strict_types=1);

namespace Expansa\Disk\Contracts;

use Expansa\Disk\Directory;
use Expansa\Disk\File;

interface System
{
    public function clean(): Directory|File;

    public function copy(string $to): Directory|File;

    public function move(string $to): Directory|File;

    public function rename(string $name): Directory|File;

    public function delete(): bool;

    public function download(): void;
}
