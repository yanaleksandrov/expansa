<?php

declare(strict_types=1);

namespace Expansa\Disk\Contracts;

use Expansa\Disk\File;

interface FileSystem
{
    public function chmod(int $mode = 0755): File;

    public function rewrite(array $content): File;

    public function write(mixed $content, bool $after = true): File;

    public function read(): string;

    public function touch(?int $time = null, ?int $atime = null): File;

    public function upload(array $file): File;

    public function grab(string $url): File;
}
