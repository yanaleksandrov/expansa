<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * Console terminal facade: commands registry and CLI input handling.
 *
 * @method static array                          getOptions()
 * @method static bool|string|null               getOption(string $option)
 * @method static array                          getArguments()
 * @method static string|null                    getArgument(int $position)
 * @method static \Expansa\Console\Terminal      addCommand(\Expansa\Console\Command|string $command)
 * @method static \Expansa\Console\Terminal      addCommands(array $commands)
 * @method static \Expansa\Console\Command|null  getCommand(string $name)
 * @method static array                          getCommands()
 * @method static \Expansa\Console\Terminal      removeCommand(string $name)
 * @method static \Expansa\Console\Terminal      removeCommands(array $names)
 * @method static bool                           hasCommand(string $name)
 * @method static void                           handle()
 * @method static void                           exec(string $command)
 */
class Terminal extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Console\Terminal';
    }
}
