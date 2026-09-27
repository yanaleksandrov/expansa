<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Filesystem\Directory;
use Expansa\Filesystem\File;
use Expansa\Patterns\Facade;

/**
 * Disk Facade provides static access to manage local filesystem.
 *
 * @method static File file(string $path)
 * @method static Directory dir(string $path)
 * @method static File upload(array $file, string $directory)
 * @method static File grab(string $url, string $directory)
 * @method static int getMaxUploadSize()
 */
class Disk extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Filesystem\Disk::class;
    }
}
