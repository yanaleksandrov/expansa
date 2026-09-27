<?php

declare(strict_types=1);

namespace Expansa\Console\Contracts;

/**
 * A console command the terminal can run.
 *
 * @package Expansa\Console
 */
interface Command
{
    /**
     * Run the command.
     *
     * @return void
     */
    public function handle(): void;
}
