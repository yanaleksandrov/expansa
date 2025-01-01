<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Console\Terminal;
use Expansa\I18n;
use Expansa\Console\Command;
use Expansa\Console\Styles\TextColor;

/**
 * Class List.
 *
 * @package cli
 */
class Index extends Command
{
    protected string $name = 'list';
    protected string $description = 'Shows full list of Expansa CLI commands.';
    protected string $signature = 'list';
    protected array $options = [
        '-g' => 'Shows greeting.',
    ];

	public function __construct(?Terminal $console = null)
	{
		parent::__construct($console);

		$this->setOptions([
			'-g'  => I18n::_t('Shows greeting'),
			'-he' => I18n::_t('New howing'),
		]);
	}

	public function handle() : void
    {
	    $text = <<<EOT
		   ____            __                      
		  / ___|_ __ __ _ / _| ___ _ __ ___   __ _ 
		 | |  _| '__/ _` | |_ / _ \ '_ ` _ \ / _` |
		 | |_| | | | (_| |  _|  __/ | | | | | (_| |
		  \____|_|  \__,_|_|  \___|_| |_| |_|\__,_|


		EOT;

		$version = EX_VERSION;
		$year    = date('Y');

	    $this->info("[green]#$text#Program version: [green]#$version# | © 2024-$year «expansa.com»".PHP_EOL);

        if ($this->console->getOption('g')) {
	        $this->info(I18n::_f('Hello, friend!'));
        }

	    $groupDefault = [];
	    $groups = [];
	    foreach ($this->console->getCommands() as $name => $command) {
		    $group = $command->getGroup();
		    if ($group === null) {
			    $groupDefault[$name] = $command;
			    continue;
		    }
		    $groups[$group][$name] = $command;
	    }

	    $this->info(I18n::_t('[yellow]#Available Commands:#'));

	    foreach ($groupDefault as $name => $command) {
		    $this->info(str_pad("  [green]#$name#", 32) . $command->getDescription());
	    }

	    ksort($groups);

	    foreach ($groups as $groupName => $commands) {
		    $this->newLine();
		    $this->info("'  [yellow]#$groupName#'");

		    foreach ($commands as $name => $command) {
			    $this->info(str_pad("  [green]#$name#", 32) . $command->getDescription());
		    }
	    }
	    $this->newLine();
    }
}
