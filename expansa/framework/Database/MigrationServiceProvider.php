<?php declare(strict_types=1);

namespace Expansa\Database;

use Expansa\Database\Commands\MigrateCommand;
use Expansa\Database\Commands\MigrationMakeCommand;
use Expansa\Database\Commands\Migrations\InstallCommand;
use Expansa\Database\Commands\Migrations\RefreshCommand;
use Expansa\Database\Commands\Migrations\ResetCommand;
use Expansa\Database\Commands\Migrations\RollbackCommand;
use Expansa\Database\Commands\Migrations\StatusCommand;
use Expansa\Database\Migrations\Migrator;
use Expansa\Database\Migrations\Repository;
use Expansa\Framework\Providers\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'migrator' => [Migrator::class],
    ];

    public array $commands = [
        'make:migration' => MigrationMakeCommand::class,
        'migrate' => MigrateCommand::class,
        'migrate:install' => InstallCommand::class,
        'migrate:reset' => ResetCommand::class,
        'migrate:status' => StatusCommand::class,
        'migrate:refresh' => RefreshCommand::class,
        'migrate:rollback' => RollbackCommand::class,
    ];

    public function register()
    {
        $this->app->singleton('migration.repository', function ($app) {
            return new Repository($app['db'], $app['config']['database.migrations'] ?? []);
        });

        $this->app->singleton('migrator', function ($app) {
            return new Migrator($app['db'], $app['migration.repository']);
        });

        $this->commands($this->commands);
    }
}