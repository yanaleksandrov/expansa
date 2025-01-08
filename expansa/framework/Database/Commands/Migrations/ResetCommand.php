<?php

declare(strict_types=1);

namespace Expansa\Database\Commands\Migrations;

use Expansa\Console\Command\Command;
use Expansa\Console\Input\InputOption;
use Expansa\Database\Migrations\Migrator;

class ResetCommand extends Command
{
    public static string $defaultName = 'migrate:reset';

    public static string $defaultDescription = 'Create the migration repository';

    public function __construct(
        protected Migrator $migrate
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->migrate->setOutput($this->output);
        $this->migrate->setConnection($this->input->getOption('database'));

        $paths = [
            realpath($this->container->basePath('/database/migrations'))
        ];

        $this->migrate->dispatch('reset', $paths);

        return 0;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'),
            new InputOption('force', null, InputOption::VALUE_OPTIONAL, 'Force the operation to run when in production'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_ARRAY, 'The path(s) to the migrations files to use'),
            new InputOption('realpath', null, InputOption::VALUE_OPTIONAL, 'Indicate any provided migration file paths are pre-resolved absolute paths'),
            new InputOption('pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run'),
        ];
    }
}
