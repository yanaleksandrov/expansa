<?php

declare(strict_types=1);

namespace Plugins\FileManager;

/**
 * What to do with an entry whose name is taken in the target folder.
 *
 * @package Plugins\FileManager
 */
enum Conflict: string
{
    case Keep    = 'keep';
    case Replace = 'replace';
    case Skip    = 'skip';
}
