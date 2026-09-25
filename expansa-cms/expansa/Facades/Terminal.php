<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Console\Command;
use Expansa\Console\Terminal as Console;
use Expansa\Patterns\Facade;

/**
 * Console terminal facade: commands registry and CLI input handling.
 *
 * @method static string           version()
 * @method static array            options()
 * @method static bool|string|null option(string $option)
 * @method static array            arguments()
 * @method static string|null      argument(int $position)
 * @method static Console          addCommand(Command|string $command)
 * @method static Console          addCommands(array $commands)
 * @method static Command|null     command(string $name)
 * @method static array            commands()
 * @method static Console          removeCommand(string $name)
 * @method static Console          removeCommands(array $names)
 * @method static bool             hasCommand(string $name)
 * @method static void             run(?string $command = null)
 */
class Terminal extends Facade
{
    private static string $version = '';

    /**
     * Set the application version; the terminal itself is only created once a console command runs.
     */
    public static function configure(string $version): void
    {
        self::$version = $version;
    }

    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Console\Terminal::class;
    }

    /**
     * @return array{0: string}
     */
    protected static function getConstructorArgs(): array
    {
        return [self::$version];
    }
}
