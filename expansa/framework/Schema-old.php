<?php

declare(strict_types=1);

namespace Expansa\Database;

use Closure;
use Expansa\Database\Contracts\DatabaseException;

class Schema
{
    /**
     * @throws DatabaseException
     */
    public function __construct(
        protected mixed $connection = null,
        protected mixed $driver = null,
        protected mixed $factory = new ConnectionFactory(),
        protected array $drivers = [
            'mysql'  => \Expansa\Database\Drivers\MySQL\Connection::class,
            'pgsql'  => \Expansa\Database\Drivers\Postgres\Connection::class,
            'sqlite' => \Expansa\Database\Drivers\SQLite\Connection::class,
        ],
        protected array $connectors = [
            'mysql'  => \Expansa\Database\Drivers\MySQL\Connector::class,
            'pgsql'  => \Expansa\Database\Drivers\Postgres\Connector::class,
            'sqlite' => \Expansa\Database\Drivers\SQLite\Connector::class,
        ],
    )
    {
        $driver = EX_DB_DRIVER;
        if (!isset($this->drivers[$driver])) {
            throw new DatabaseException("Driver [$driver] is not supported");
        }

        $config    = [
            'database' => EX_DB_NAME,
            'username' => EX_DB_USERNAME,
            'password' => EX_DB_PASSWORD,
            'host'     => EX_DB_HOST,
            'prefix'   => EX_DB_PREFIX,
            'driver'   => EX_DB_DRIVER,
            'charset'  => EX_DB_CHARSET,
        ];

        $connector = (new $this->connectors[$driver]())->connect($config);

        echo '<pre>';
        print_r($connector);
        echo '</pre>';
        $this->connection = new $this->drivers[$driver]($connector, $config);
    }

    public function create(string $table, Closure $callback)
    {
        return $this->driver->create($table, $callback);
    }
}
