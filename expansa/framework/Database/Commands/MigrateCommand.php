<?php declare(strict_types=1);

namespace Expansa\Database\Commands;

use Expansa\Console\Command\Command;
use Expansa\Console\Input\InputOption;
use Expansa\Database\Migrations\Migrator;

class MigrateCommand extends Command
{
    public static string $defaultName = 'migrate';

    public static string $defaultDescription = 'Run the database migrations';

    public function __construct(
        protected Migrator $migrator
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->migrator->setOutput($this->output);
        $this->migrator->setConnection($this->input->getOption('database'));

        $this->prepareDatabase();

        $this->migrator->dispatch('migrate', [
	        realpath( $this->container->basePath('/database/migrations'))
        ]);

        $this->output->newLine();

        return 0;
    }

    protected function prepareDatabase(): void
    {
        if ($this->migrator->getRepository()->repositoryExists()) {
            return;
        }

        $this->components()->info('Preparing database');

        $this->components()->task('Creating migration table', function () {
            $this->migrator->getRepository()->createRepository();
        });

        $this->output->newLine();
    }

    public function getOptions(): array
    {
        return [
            new InputOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'),
            new InputOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_ARRAY, 'The path(s) to the migrations files to use'),
            new InputOption('realpath', null, InputOption::VALUE_OPTIONAL, 'Indicate any provided migration file paths are pre-resolved absolute paths'),
        ];
    }
}