<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Filesystem\Directory;
use Expansa\Filesystem\File;
use Expansa\Patterns\Facade;

/**
 * Disk Facade provides static access to manage local filesystem.
 *
 * @method static File file(string $filepath)
 * @method static Directory dir(string $dirpath)
 */
class Disk extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Filesystem\Disk';
    }
}
