<?php

declare(strict_types=1);

namespace Expansa\Database;

use Expansa\Database\Contracts\DatabaseException;
use Expansa\Database\Traits\DetectsErrors;
use PDO;

abstract class Connector
{
    use DetectsErrors;

    protected array $config;

    protected array $options = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => false,
    ];

    /**
     * @throws DatabaseException
     */
    public function connect(array $config): PDO
    {
        $this->config = $config;

        return $this->create($this->getDsn(), $this->getOptions());
    }

    /**
     * @throws DatabaseException
     */
    protected function create(string $dsn, array $options = []): PDO
    {
        [$username, $password] = [
            $this->config['username'] ?? null, $this->config['password'] ?? null,
        ];

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (\Exception $e) {
            if ($this->causedByLostConnection($e)) {
                return new PDO($dsn, $username, $password, $options);
            }

            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getDsn(): string
    {
        $dsn = "{$this->config['driver']}:";

        if (isset($this->config['host'])) {
            $dsn .= "host={$this->config['host']};";
        }

        if (isset($this->config['port'])) {
            $dsn .= "port={$this->config['port']};";
        }

        if (isset($this->config['database'])) {
            $dsn .= "dbname={$this->config['database']};";
        }

        if (isset($this->config['charset'])) {
            $dsn .= "charset={$this->config['charset']};";
        }

        return $dsn;
    }

    public function getOptions()
    {
        $options = $this->config['options'] ?? [];

        return array_diff_key($this->options, $options) + $options;
    }
}
