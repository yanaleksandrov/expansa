<?php

declare(strict_types=1);

namespace Expansa\Database;

use Expansa\Database\Contracts\Connection as ConnectionContract;
use Expansa\Database\Contracts\ConnectionResolver;
use Expansa\Database\Contracts\DatabaseException;

class DatabaseManager implements ConnectionResolver
{
    protected ConnectionFactory $factory;

    protected array $connections = [];

    protected \Closure $reconnector;

    protected array $drivers = [
        'mysql'  => [
            'connection' => \Expansa\Database\Drivers\MySQL\Connection::class,
            'connector'  => \Expansa\Database\Drivers\MySQL\Connector::class,
        ],
        'pgsql'  => [
            'connection' => \Expansa\Database\Drivers\Postgres\Connection::class,
            'connector'  => \Expansa\Database\Drivers\Postgres\Connector::class,
        ],
        'sqlite' => [
            'connection' => \Expansa\Database\Drivers\SQLite\Connection::class,
            'connector'  => \Expansa\Database\Drivers\SQLite\Connector::class,
        ],
    ];

    public function __construct()
    {
        $this->factory     = new ConnectionFactory();
        $this->reconnector = fn (ConnectionContract $connection) => $this->reconnect($connection->getName());
    }

    public function connection(string $name = null): ConnectionContract
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->configureConnection(
                $this->makeConnection($name)
            );
        }

        return $this->connections[$name];
    }

    public function reconnect(string $name = null): ConnectionContract
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        $this->disconnect($name);

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        $newConn = $this->makeConnection($name);

        return $this->connections[$name]
            ->setPdo($newConn->getPdo())
            ->setReadPdo($newConn->getReadPdo());
    }

    public function disconnect(string $name = null): void
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
        }
    }

    public function purge(string $name = null): void
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    protected function makeConnection($name): ConnectionContract
    {
        $config = $this->getConfig($name);

        if (!isset($this->drivers[$config['driver']])) {
            throw new DatabaseException("Driver [{$name}] is not supported");
        }

        return $this->factory->make($this->drivers[$config['driver']], $config);
    }

    protected function configureConnection(ConnectionContract $connection, string $type = null): ConnectionContract
    {
        if ($this->app->bound('events')) {
            $connection->setEventDispatcher($this->app['events']);
        }

        $connection->setReconnector($this->reconnector);

        return $connection;
    }

    public function getDefaultConnection(): string
    {
        return config()->get('database.default');
    }

    public function setDefaultConnection(string $name): void
    {
        config()->set('database.default', $name);
    }

    protected function getConfig($name): array
    {
        $config = config()->get("database.connections.{$name}");

        if (is_null($config)) {
            throw new DatabaseException("Connection [{$name}] not configured");
        }

        $config['name'] = $name;

        return $config;
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
