<?php

declare(strict_types=1);

namespace Grafema\Console\Contracts;

/**
 * Command Interface
 *
 * This interface defines the structure for a command class in the CLI application.
 *
 * @package Grafema\Console
 */
interface Commands
{
	/**
	 * Handle the execution of the command.
	 *
	 * This method should contain the logic to perform the command's main function.
	 *
	 * @return void
	 */
	public function handle(): void;
}
