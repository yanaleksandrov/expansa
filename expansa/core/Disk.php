<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;
use Expansa\Disk\Directory;
use Expansa\Disk\File;

/**
 * Disk Facade
 *
 * Provides static access to the Disk subsystem.
 *
 * @method static File file(string $filepath)
 * @method static Directory dir(string $dirpath)
 */
class Disk extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Disk\Disk';
    }
}
