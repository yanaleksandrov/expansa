<?php

declare(strict_types=1);

namespace Expansa\Database;

use Expansa\Database\Contracts\ConnectionResolver;
use Expansa\Database\Commands\MigrateCommand;
use Expansa\Framework\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public array $aliases = [
        'db' => [DatabaseManager::class, ConnectionResolver::class],
    ];

    public function register()
    {
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app);
        });

        $this->commands([
            'migrate' => MigrateCommand::class,
        ]);

        $this->app->bind('scheme', function ($app) {
            return $app['db']->getSchemaBuilder();
        });
    }
}
