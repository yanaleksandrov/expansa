<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Disk\Directory;
use Expansa\Disk\File;
use Expansa\Facades\Facade;

/**
 * @method static array read(int $depth = 0)
 * @method static array directoriesTree(): array
 * @method static array files(string $pattern = '*', int $depth = 0)
 * @method static Directory make(int $mode = 0755)
 * @method static Directory chmod(int $permission)
 * @method static Directory clean()
 * @method static Directory copy(string $to)
 * @method static bool delete()
 * @method static void download()
 * @method static bool exists()
 * @method static Directory move(string $to)
 * @method static string getSameSite()
 */
class Dir extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Disk\Directory';
    }
}
