<?php

declare(strict_types=1);

namespace Expansa\Database\Query;

use Expansa\Database\Exception\InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;

class Builder extends BuilderAbstract
{
    /**
     * Connect the database.
     *
     * @param array $options Connection options
     * @return void
     * @throws PDOException|InvalidArgumentException
     */
    public function __construct(array $options)
    {
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if (isset($options['testMode']) && $options['testMode'] === true) {
            $this->testMode = true;
            return;
        }

        $options['type'] = $options['type'] ?? $options['driver'];

        if (!isset($options['pdo'])) {
            $options['database'] = $options['database'] ?? $options['database_name'];

            if (!isset($options['socket'])) {
                $options['host'] = $options['host'] ?? $options['server'] ?? false;
            }
        }

        if (isset($options['type'])) {
            $this->type = strtolower($options['type']);

            if ($this->type === 'mariadb') {
                $this->type = 'mysql';
            }
        }

        if (isset($options['logging']) && is_bool($options['logging'])) {
            $this->logging = $options['logging'];
        }

        $option = $options['option'] ?? [];

        $commands = match ($this->type) {
            'mysql' => ['SET SQL_MODE=ANSI_QUOTES'], // Make MySQL using standard quoted identifier.
            'mssql' => [
                'SET QUOTED_IDENTIFIER ON',          // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting.
                'SET ANSI_NULLS ON',                 // Make ANSI_NULLS is ON for NULL value.
            ],
            default => [],
        };

        if (isset($options['pdo'])) {
            if (!$options['pdo'] instanceof PDO) {
                throw new InvalidArgumentException('Invalid PDO object supplied.');
            }

            $this->pdo = $options['pdo'];

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }

            return;
        }

        if (isset($options['dsn'])) {
            if (is_array($options['dsn']) && isset($options['dsn']['driver'])) {
                $attr = $options['dsn'];
            } else {
                throw new InvalidArgumentException('Invalid DSN option supplied.');
            }
        } else {
            if (
                isset($options['port']) &&
                is_int($options['port'] * 1)
            ) {
                $port = $options['port'];
            }

            $isPort = isset($port);

            switch ($this->type) {
                case 'mysql':
                    $attr = [
                        'driver' => 'mysql',
                        'dbname' => $options['database'],
                    ];

                    if (isset($options['socket'])) {
                        $attr['unix_socket'] = $options['socket'];
                    } else {
                        $attr['host'] = $options['host'];

                        if ($isPort) {
                            $attr['port'] = $port;
                        }
                    }

                    break;

                case 'pgsql':
                    $attr = [
                        'driver' => 'pgsql',
                        'host'   => $options['host'],
                        'dbname' => $options['database'],
                    ];

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'sybase':
                    $attr = [
                        'driver' => 'dblib',
                        'host'   => $options['host'],
                        'dbname' => $options['database'],
                    ];

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'oracle':
                    $attr = [
                        'driver' => 'oci',
                        'dbname' => $options['host'] ?
                            '//' . $options['host'] . ($isPort ? ':' . $port : ':1521') . '/' . $options['database'] :
                            $options['database'],
                    ];

                    if (isset($options['charset'])) {
                        $attr['charset'] = $options['charset'];
                    }

                    break;

                case 'mssql':
                    if (isset($options['driver']) && $options['driver'] === 'dblib') {
                        $attr = [
                            'driver' => 'dblib',
                            'host'   => $options['host'] . ($isPort ? ':' . $port : ''),
                            'dbname' => $options['database'],
                        ];

                        if (isset($options['appname'])) {
                            $attr['appname'] = $options['appname'];
                        }

                        if (isset($options['charset'])) {
                            $attr['charset'] = $options['charset'];
                        }
                    } else {
                        $attr = [
                            'driver'   => 'sqlsrv',
                            'Server'   => $options['host'] . ($isPort ? ',' . $port : ''),
                            'Database' => $options['database'],
                        ];

                        if (isset($options['appname'])) {
                            $attr['APP'] = $options['appname'];
                        }

                        $config = [
                            'ApplicationIntent',
                            'AttachDBFileName',
                            'Authentication',
                            'ColumnEncryption',
                            'ConnectionPooling',
                            'Encrypt',
                            'Failover_Partner',
                            'KeyStoreAuthentication',
                            'KeyStorePrincipalId',
                            'KeyStoreSecret',
                            'LoginTimeout',
                            'MultipleActiveResultSets',
                            'MultiSubnetFailover',
                            'Scrollable',
                            'TraceFile',
                            'TraceOn',
                            'TransactionIsolation',
                            'TransparentNetworkIPResolution',
                            'TrustServerCertificate',
                            'WSID',
                        ];

                        foreach ($config as $value) {
                            $keyname = strtolower(
                                preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $value)
                            );

                            if (isset($options[$keyname])) {
                                $attr[$value] = $options[$keyname];
                            }
                        }
                    }

                    break;

                case 'sqlite':
                    $attr = [
                        'driver' => 'sqlite',
                        $options['database']
                    ];

                    break;
            }
        }

        if (!isset($attr)) {
            throw new InvalidArgumentException('Incorrect connection options.');
        }

        $driver = $attr['driver'];

        if (!in_array($driver, PDO::getAvailableDrivers())) {
            throw new InvalidArgumentException("Unsupported PDO driver: {$driver}.");
        }

        unset($attr['driver']);

        $stack = [];

        foreach ($attr as $key => $value) {
            $stack[] = is_int($key) ? $value : $key . '=' . $value;
        }

        $dsn = $driver . ':' . implode(';', $stack);

        if (
            in_array($this->type, ['mysql', 'pgsql', 'sybase', 'mssql']) &&
            isset($options['charset'])
        ) {
            $commands[] = "SET NAMES '{$options['charset']}'" . (
                $this->type === 'mysql' && isset($options['collation']) ?
                    " COLLATE '{$options['collation']}'" : ''
                );
        }

        $this->dsn = $dsn;

        try {
            $this->pdo = new PDO(
                $dsn,
                $options['username'] ?? null,
                $options['password'] ?? null,
                $option
            );

            if (isset($options['error'])) {
                $this->pdo->setAttribute(
                    PDO::ATTR_ERRMODE,
                    in_array($options['error'], [
                        PDO::ERRMODE_SILENT,
                        PDO::ERRMODE_WARNING,
                        PDO::ERRMODE_EXCEPTION,
                    ]) ?
                        $options['error'] :
                        PDO::ERRMODE_SILENT
                );
            }

            if (isset($options['command']) && is_array($options['command'])) {
                $commands = array_merge($commands, $options['command']);
            }

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Execute customized raw statement.
     *
     * @param string $statement The raw SQL statement.
     * @param array $map The array of input parameters value for prepared statement.
     * @return PDOStatement|null
     */
    public function query(string $statement, array $map = []): ?PDOStatement
    {
        $raw = $this->raw($statement, $map);
        $statement = $this->buildRaw($raw, $map);

        return $this->exec($statement, $map);
    }

    /**
     * Build a raw object.
     *
     * @param string $string The raw string.
     * @param array $map The array of mapping data for the raw string.
     * @return Raw
     */
    public static function raw(string $string, array $map = []): Raw
    {
        $raw = new Raw();

        $raw->map   = $map;
        $raw->value = $string;

        return $raw;
    }

    /**
     * Quote a string for use in a query.
     *
     * @param string $string
     * @return string
     */
    public function quote(string $string): string
    {
        if ($this->type === 'mysql') {
            return "'" . preg_replace(['/([\'"])/', '/(\\\\\\\")/'], ["\\\\\${1}", '\\\${1}'], $string) . "'";
        }

        return "'" . preg_replace('/\'/', '\'\'', $string) . "'";
    }

    /**
     * Create a table.
     *
     * @param string     $table
     * @param array      $columns Columns definition.
     * @param null|array $options Additional table options for creating a table.
     * @return PDOStatement|null
     */
    public function create(string $table, array $columns, array $options = null): ?PDOStatement
    {
        $stack = [];
        $tableOption = '';
        $tableName = $this->tableQuote($table);

        foreach ($columns as $name => $definition) {
            if (is_int($name)) {
                $stack[] = preg_replace("/\<(" . $this::COLUMN_PATTERN . ")\>/u", '"$1"', $definition);
            } elseif (is_array($definition)) {
                $stack[] = $this->columnQuote($name) . ' ' . implode(' ', $definition);
            } elseif (is_string($definition)) {
                $stack[] = $this->columnQuote($name) . ' ' . $definition;
            }
        }

        if (is_array($options)) {
            $optionStack = [];

            foreach ($options as $key => $value) {
                if (is_string($value) || is_int($value)) {
                    $optionStack[] = "$key = $value";
                }
            }

            $tableOption = ' ' . implode(', ', $optionStack);
        } elseif (is_string($options)) {
            $tableOption = ' ' . $options;
        }

        $command = 'CREATE TABLE';

        if (in_array($this->type, ['mysql', 'pgsql', 'sqlite'])) {
            $command .= ' IF NOT EXISTS';
        }

        return $this->exec("$command $tableName (" . implode(', ', $stack) . ")$tableOption");
    }

    /**
     * Drop a table.
     *
     * @param string $table
     * @return PDOStatement|null
     */
    public function drop(string $table): ?PDOStatement
    {
        return $this->exec("DROP TABLE IF EXISTS {$this->tableQuote($table)}");
    }

    /**
     * Rename a table.
     *
     * @param string $table
     * @param string $to
     * @return PDOStatement|null
     */
    public function rename(string $table, string $to): ?PDOStatement
    {
        return $this->exec("RENAME TABLE {$this->tableQuote($table)} TO {$this->tableQuote($to)}");
    }

    /**
     * Select data from the table.
     *
     * @param string            $table
     * @param array             $join
     * @param null|array|string $columns
     * @param null|array        $where
     * @return array|null
     */
    public function select(string $table, $join, array|string $columns = null, array $where = null): ?array
    {
        $map       = [];
        $result    = [];
        $columnMap = [];

        $args = func_get_args();
        $lastArgs = $args[array_key_last($args)];
        $callback = is_callable($lastArgs) ? $lastArgs : null;

        $where   = is_callable($where) ? null : $where;
        $columns = is_callable($columns) ? null : $columns;

        $column = $where === null ? $join : $columns;
        $isSingle = (is_string($column) && $column !== '*');

        $statement = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        $this->columnMap($columns, $columnMap, true);

        if (!$this->statement) {
            return $result;
        }

        if ($columns === '*') {
            if (isset($callback)) {
                while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
                    $callback($data);
                }

                return null;
            }

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }

        while ($data = $statement->fetch(PDO::FETCH_ASSOC)) {
            $currentStack = [];

            if (isset($callback)) {
                $this->dataMap($data, $columns, $columnMap, $currentStack, true);

                $callback(
                    $isSingle ?
                        $currentStack[$columnMap[$column][0]] :
                        $currentStack
                );
            } else {
                $this->dataMap($data, $columns, $columnMap, $currentStack, true, $result);
            }
        }

        if (isset($callback)) {
            return null;
        }

        if ($isSingle) {
            $singleResult = [];
            $resultKey = $columnMap[$column][0];

            foreach ($result as $item) {
                $singleResult[] = $item[$resultKey];
            }

            return $singleResult;
        }

        return $result;
    }

    /**
     * Insert one or more records into the table.
     *
     * @param string      $table
     * @param array       $values
     * @param null|string $primaryKey
     * @return PDOStatement|null
     */
    public function insert(string $table, array $values, ?string $primaryKey = null): ?PDOStatement
    {
        $stack     = [];
        $columns   = [];
        $fields    = [];
        $map       = [];
        $returning = [];

        if (!isset($values[0])) {
            $values = [$values];
        }

        foreach ($values as $data) {
            foreach ($data as $key => $value) {
                $columns[] = $key;
            }
        }

        $columns = array_unique($columns);

        foreach ($values as $data) {
            $values = [];

            foreach ($columns as $key) {
                $value = $data[$key];
                $type = gettype($value);

                if ($this->type === 'oracle' && $type === 'resource') {
                    $values[] = 'EMPTY_BLOB()';
                    $returning[$this->mapKey()] = [$key, $value, PDO::PARAM_LOB];
                    continue;
                }

                if ($raw = $this->buildRaw($data[$key], $map)) {
                    $values[] = $raw;
                    continue;
                }

                $mapKey = $this->mapKey();
                $values[] = $mapKey;

                switch ($type) {
                    case 'array':
                        $map[$mapKey] = [
                            strpos($key, '[JSON]') === strlen($key) - 6 ?
                                json_encode($value) :
                                serialize($value),
                            PDO::PARAM_STR,
                        ];
                        break;
                    case 'NULL':
                    case 'resource':
                    case 'boolean':
                    case 'integer':
                    case 'double':
                    case 'string':
                        $map[$mapKey] = $this->typeMap($value, $type);
                        break;
                }
            }

            $stack[] = '(' . implode(', ', $values) . ')';
        }

        foreach ($columns as $key) {
            $fields[] = $this->columnQuote(preg_replace("/(\s*\[JSON\]$)/i", '', $key));
        }

        $query = 'INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack);

        if (
            $this->type === 'oracle' && (!empty($returning) || isset($primaryKey))
        ) {
            if ($primaryKey) {
                $returning[':RETURNID'] = [$primaryKey, '', PDO::PARAM_INT, 8];
            }

            $statement = $this->returningQuery($query, $map, $returning);

            if ($primaryKey) {
                $this->returnId = $returning[':RETURNID'][1];
            }

            return $statement;
        }

        return $this->exec($query, $map);
    }

    /**
     * Modify data from the table.
     *
     * @param string     $table
     * @param array      $data
     * @param null|array $where
     * @return PDOStatement|null
     */
    public function update(string $table, array $data, array $where = null): ?PDOStatement
    {
        $fields = [];
        $map = [];
        $returning = [];

        foreach ($data as $key => $value) {
            $column = $this->columnQuote(preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/", '', $key));
            $type   = gettype($value);

            if ($this->type === 'oracle' && $type === 'resource') {
                $fields[] = "{$column} = EMPTY_BLOB()";
                $returning[$this->mapKey()] = [$key, $value, PDO::PARAM_LOB];
                continue;
            }

            if ($raw = $this->buildRaw($value, $map)) {
                $fields[] = "{$column} = {$raw}";
                continue;
            }

            preg_match("/" . $this::COLUMN_PATTERN . "(\[(?<operator>\+|\-|\*|\/)\])?/u", $key, $match);

            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $fields[] = "$column = $column {$match['operator']} $value";
                }
            } else {
                $mapKey = $this->mapKey();
                $fields[] = "$column = $mapKey";

                switch ($type) {
                    case 'array':
                        $map[$mapKey] = [
                            strpos($key, '[JSON]') === strlen($key) - 6 ?
                                json_encode($value) :
                                serialize($value),
                            PDO::PARAM_STR,
                        ];
                        break;
                    case 'NULL':
                    case 'resource':
                    case 'boolean':
                    case 'integer':
                    case 'double':
                    case 'string':
                        $map[$mapKey] = $this->typeMap($value, $type);
                        break;
                }
            }
        }

        $query = 'UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map);

        if ($this->type === 'oracle' && !empty($returning)) {
            return $this->returningQuery($query, $map, $returning);
        }

        return $this->exec($query, $map);
    }

    /**
     * Delete data from the table.
     *
     * @param string    $table
     * @param array|Raw $where
     * @return PDOStatement|null
     */
    public function delete(string $table, Raw|array $where): ?PDOStatement
    {
        $map = [];

        return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
    }

    /**
     * Replace old data with a new one.
     *
     * @param string     $table
     * @param array      $columns
     * @param null|array $where
     * @return PDOStatement|null
     * @throws InvalidArgumentException
     */
    public function replace(string $table, array $columns, array $where = null): ?PDOStatement
    {
        $map   = [];
        $stack = [];

        foreach ($columns as $column => $replacements) {
            if (!is_array($replacements)) {
                continue;
            }

            foreach ($replacements as $old => $new) {
                $mapKey     = $this->mapKey();
                $columnName = $this->columnQuote($column);

                $stack[] = "$columnName = REPLACE($columnName, {$mapKey}a, {$mapKey}b)";

                $map[$mapKey . 'a'] = [$old, PDO::PARAM_STR];
                $map[$mapKey . 'b'] = [$new, PDO::PARAM_STR];
            }
        }

        if (empty($stack)) {
            throw new InvalidArgumentException('Invalid columns supplied.');
        }

        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $stack) . $this->whereClause($where, $map), $map);
    }

    /**
     * Get only one record from the table.
     *
     * @param string            $table
     * @param null|array        $join
     * @param null|array|string $columns
     * @param null|array        $where
     * @return mixed
     */
    public function get(string $table, $join = null, array|string $columns = null, array $where = null): mixed
    {
        $map          = [];
        $result       = [];
        $columnMap    = [];
        $currentStack = [];

        if ($where === null) {
            if ($this->isJoin($join)) {
                $where['LIMIT'] = 1;
            } else {
                $columns['LIMIT'] = 1;
            }

            $column = $join;
        } else {
            $column = $columns;
            $where['LIMIT'] = 1;
        }

        $isSingle = (is_string($column) && $column !== '*');
        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        if (!$this->statement) {
            return false;
        }

        $data = $query->fetchAll(PDO::FETCH_ASSOC);

        if (isset($data[0])) {
            if ($column === '*') {
                return $data[0];
            }

            $this->columnMap($columns, $columnMap, true);
            $this->dataMap($data[0], $columns, $columnMap, $currentStack, true, $result);

            if ($isSingle) {
                return $result[0][$columnMap[$column][0]];
            }

            return $result[0];
        }

        return null;
    }

    /**
     * Determine whether the target data existed from the table.
     *
     * @param string     $table
     * @param array      $join
     * @param null|array $where
     * @return bool
     */
    public function has(string $table, array $join, array $where = null): bool
    {
        $map = [];
        $column = null;

        $query = $this->exec(
            $this->type === 'mssql' ?
                $this->selectContext($table, $map, $join, $column, $where, self::raw('TOP 1 1')) :
                'SELECT EXISTS(' . $this->selectContext($table, $map, $join, $column, $where, '1') . ')',
            $map
        );

        if (!$this->statement) {
            return false;
        }
        $result = $query->fetchColumn();

        return $result === '1' || $result === 1 || $result === true;
    }

    /**
     * Randomly fetch data from the table.
     *
     * @param string            $table
     * @param null|array        $join
     * @param null|array|string $columns
     * @param null|array        $where
     * @return array
     */
    public function rand(string $table, array $join = null, array|string $columns = null, array $where = null): array
    {
        $orderRaw = $this->raw(
            $this->type === 'mysql' ? 'RAND()'
                : ($this->type === 'mssql' ? 'NEWID()' : 'RANDOM()')
        );

        if ($where === null) {
            if ($this->isJoin($join)) {
                $where['ORDER'] = $orderRaw;
            } else {
                $columns['ORDER'] = $orderRaw;
            }
        } else {
            $where['ORDER'] = $orderRaw;
        }

        return $this->select($table, $join, $columns, $where);
    }

    /**
     * Count the number of rows from the table.
     *
     * @param string      $table
     * @param null|array  $join
     * @param null|string $column
     * @param null|array  $where
     * @return int|null
     */
    public function count(string $table, array $join = null, string $column = null, array $where = null): ?int
    {
        return (int) $this->aggregate('COUNT', $table, $join, $column, $where);
    }

    /**
     * Calculate the average value of the column.
     *
     * @param string      $table
     * @param array       $join
     * @param null|string $column
     * @param null|array  $where
     * @return null|string
     */
    public function avg(string $table, array $join, string $column = null, array $where = null): ?string
    {
        return $this->aggregate('AVG', $table, $join, $column, $where);
    }

    /**
     * Get the maximum value of the column.
     *
     * @param string      $table
     * @param array       $join
     * @param null|string $column
     * @param null|array  $where
     * @return null|string
     */
    public function max(string $table, array $join, string $column = null, array $where = null): ?string
    {
        return $this->aggregate('MAX', $table, $join, $column, $where);
    }

    /**
     * Get the minimum value of the column.
     *
     * @param string      $table
     * @param array       $join
     * @param null|string $column
     * @param null|array  $where
     * @return null|string
     */
    public function min(string $table, array $join, string $column = null, array $where = null): ?string
    {
        return $this->aggregate('MIN', $table, $join, $column, $where);
    }

    /**
     * Calculate the total value of the column.
     *
     * @param string      $table
     * @param array       $join
     * @param null|string $column
     * @param null|array  $where
     * @return null|string
     */
    public function sum(string $table, array $join, string $column = null, array $where = null): ?string
    {
        return $this->aggregate('SUM', $table, $join, $column, $where);
    }

    /**
     * Start a transaction.
     *
     * @param callable $actions
     * @return void
     * @throws \Exception
     */
    public function action(callable $actions): void
    {
        if (is_callable($actions)) {
            $this->pdo->beginTransaction();

            try {
                $result = $actions($this);

                if ($result === false) {
                    $this->pdo->rollBack();
                } else {
                    $this->pdo->commit();
                }
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }
    }

    /**
     * Return the ID for the last inserted row.
     *
     * @param null|string $name
     * @return null|string
     */
    public function id(?string $name = null): ?string
    {
        return match ($this->type) {
            'oracle' => $this->returnId,
            'pgsql'  => (string) $this->pdo->query('SELECT LASTVAL()')->fetchColumn() ?: null,
            default  => $this->pdo->lastInsertId($name),
        };
    }

    /**
     * Enable debug mode and output readable statement string.
     *
     * @return self
     */
    public function debug(): self
    {
        $this->debugMode = true;

        return $this;
    }

    /**
     * Enable debug logging mode.
     *
     * @return void
     */
    public function beginDebug(): void
    {
        $this->debugMode    = true;
        $this->debugLogging = true;
    }

    /**
     * Disable debug logging and return all readable statements.
     *
     * @return array
     */
    public function debugLog(): array
    {
        $this->debugMode    = false;
        $this->debugLogging = false;

        return $this->debugLogs;
    }

    /**
     * Return the last performed statement.
     *
     * @return null|string
     */
    public function last(): ?string
    {
        if (empty($this->logs)) {
            return null;
        }

        $log = $this->logs[array_key_last($this->logs)];

        return $this->generate($log[0], $log[1]);
    }

    /**
     * Return all executed statements.
     *
     * @return string[]
     */
    public function log(): array
    {
        return array_map(fn ($log) => $this->generate($log[0], $log[1]), $this->logs);
    }

    /**
     * Get information about the database connection.
     *
     * @return array
     */
    public function info(): array
    {
        $output = [
            'server'     => 'SERVER_INFO',
            'driver'     => 'DRIVER_NAME',
            'client'     => 'CLIENT_VERSION',
            'version'    => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS',
        ];

        foreach ($output as $key => $value) {
            try {
                $output[$key] = $this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
            } catch (PDOException $e) {
                $output[$key] = $e->getMessage();
            }
        }

        $output['dsn'] = $this->dsn;

        return $output;
    }

    /**
     * Get database version
     */
    public function version(): string
    {
        return preg_replace(
            '/^\D*(\d+\.\d+(\.\d+)?)/',
            '$1',
            $this->pdo->getAttribute(constant('PDO::ATTR_CLIENT_VERSION'))
        ) ?: '';
    }

    /**
     * Get database schema
     *
     * @see https://stackoverflow.com/questions/52642542/how-to-extract-column-name-and-type-from-mysql
     */
    public function schema(string $col = null): array
    {
        if (! $this->schema) {
            $query = $this->query(
                '
				SELECT
    				COLUMN_NAME as name, DATA_TYPE as type, TABLE_NAME as tbl
				FROM
				    INFORMATION_SCHEMA.COLUMNS
				WHERE
				    TABLE_SCHEMA = :database',
                [
                    ':database' => EX_DB_NAME, // TODO: компонент фреймворка ничего не должен знать про константу
                ]
            );

            if ($query instanceof PDOStatement) {
                $columns = $query->fetchAll(PDO::FETCH_ASSOC);
                foreach ($columns as $column) {
                    $this->schema[$column['tbl']][$column['name']] = $column['type'];
                }
            }
        }

        if (isset($this->schema[$col])) {
            return $this->schema[$col];
        }
        return $this->schema;
    }

    public function updateSchema(): array
    {
        $this->schema = [];

        return $this->schema();
    }
}
