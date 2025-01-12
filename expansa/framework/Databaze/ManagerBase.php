<?php

declare(strict_types=1);

namespace Expansa\Databaze;

use PDO;
use PDOStatement;
use InvalidArgumentException;

abstract class ManagerBase
{
    /**
     * The PDO object.
     *
     * @var PDO
     */
    public PDO $pdo;

    /**
     * The type of database.
     *
     * @var string
     */
    public string $type = '';

    /**
     * Table prefix.
     *
     * @var string
     */
    protected mixed $prefix;

    /**
     * The PDO statement object.
     *
     * @var null|PDOStatement
     */
    protected ?PDOStatement $statement;

    /**
     * The DSN connection string.
     *
     * @var string
     */
    protected string $dsn;

    /**
     * The array of logs.
     *
     * @var array
     */
    protected array $logs = [];

    /**
     * Database schema.
     *
     * @var array
     */
    public array $schema = [];

    /**
     * Determine should log the query or not.
     *
     * @var bool
     */
    protected bool $logging = false;

    /**
     * Determine is in test mode.
     *
     * @var bool
     */
    protected bool $testMode = false;

    /**
     * The last query string was generated in test mode.
     *
     * @var string
     */
    public string $queryString;

    /**
     * Determine is in debug mode.
     *
     * @var bool
     */
    protected bool $debugMode = false;

    /**
     * Determine should save debug logging.
     *
     * @var bool
     */
    protected bool $debugLogging = false;

    /**
     * The array of logs for debugging.
     *
     * @var array
     */
    protected array $debugLogs = [];

    /**
     * The unique global id.
     *
     * @var integer
     */
    protected int $guid = 0;

    /**
     * The returned id for the insert.
     *
     * @var string
     */
    public string $returnId = '';

    /**
     * Error Message.
     *
     * @var null|string
     */
    public ?string $error = null;

    /**
     * The array of error information.
     *
     * @var array|null
     */
    public ?array $errorInfo = null;

    /**
     * Regular expression pattern for valid table names.
     *
     * @var string
     */
    protected const TABLE_PATTERN = "[\p{L}_][\p{L}\p{N}@$#\-_]*";

    /**
     * Regular expression pattern for valid column names.
     *
     * @var string
     */
    protected const COLUMN_PATTERN = "[\p{L}_][\p{L}\p{N}@$#\-_\.]*";

    /**
     * Regular expression pattern for valid alias names.
     *
     * @var string
     */
    protected const ALIAS_PATTERN = "[\p{L}_][\p{L}\p{N}@$#\-_]*";

    /**
     * Execute the raw statement.
     *
     * @param string        $statement The SQL statement.
     * @param array         $map       The array of input parameters value for prepared statement.
     * @param null|callable $callback
     * @return PDOStatement|null
     */
    protected function exec(string $statement, array $map = [], ?callable $callback = null): ?PDOStatement
    {
        $this->statement = null;
        $this->errorInfo = null;
        $this->error     = null;

        if ($this->testMode) {
            $this->queryString = $this->generate($statement, $map);
            return null;
        }

        if ($this->debugMode) {
            if ($this->debugLogging) {
                $this->debugLogs[] = $this->generate($statement, $map);
                return null;
            }

            echo $this->generate($statement, $map);

            $this->debugMode = false;

            return null;
        }

        if ($this->logging) {
            $this->logs[] = [$statement, $map];
        } else {
            $this->logs = [[$statement, $map]];
        }

        $statement = $this->pdo->prepare($statement);
        $errorInfo = $this->pdo->errorInfo();

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = $errorInfo[2];

            return null;
        }

        foreach ($map as $key => $value) {
            $statement->bindValue($key, $value[0], $value[1]);
        }

        if (is_callable($callback)) {
            $this->pdo->beginTransaction();
            $callback($statement);
            $execute = $statement->execute();
            $this->pdo->commit();
        } else {
            $execute = $statement->execute();
        }

        $errorInfo = $statement->errorInfo();

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = $errorInfo[2];

            return null;
        }

        if ($execute) {
            $this->statement = $statement;
        }

        return $statement;
    }

    /**
     * Generate a new map key for the placeholder.
     *
     * @return string
     */
    protected function mapKey(): string
    {
        return ':MeD' . $this->guid++ . '_mK';
    }

    /**
     * Generate readable statement.
     *
     * @param string $statement
     * @param array $map
     * @return string
     */
    protected function generate(string $statement, array $map): string
    {
        $identifier = [
            'mysql' => '`$1`',
            'mssql' => '[$1]',
        ];

        $statement = preg_replace(
            '/(?!\'[^\s]+\s?)"([\p{L}_][\p{L}\p{N}@$#\-_]*)"(?!\s?[^\s]+\')/u',
            $identifier[$this->type] ?? '"$1"',
            $statement
        );

        foreach ($map as $key => $value) {
            $replace = match ($value[1]) {
                PDO::PARAM_STR  => $this->quote("{$value[0]}"),
                PDO::PARAM_NULL => 'NULL',
                PDO::PARAM_LOB  => '{LOB_DATA}',
                default         => $value[0] . '',
            };

            $statement = str_replace($key, $replace, $statement);
        }

        return $statement;
    }

    /**
     * Build a raw object.
     *
     * @param string $string The raw string.
     * @param array $map The array of mapping data for the raw string.
     * @return Medoo::raw
     */
    public static function raw(string $string, array $map = []): Raw
    {
        $raw = new Raw();

        $raw->map   = $map;
        $raw->value = $string;

        return $raw;
    }

    /**
     * Finds whether the object is raw.
     *
     * @param object $object
     * @return bool
     */
    protected function isRaw(object $object): bool
    {
        return $object instanceof Raw;
    }

    /**
     * Generate the actual query from the raw object.
     *
     * @param mixed $raw
     * @param array $map
     * @return null|string
     */
    protected function buildRaw(mixed $raw, array &$map): ?string
    {
        if (!$this->isRaw($raw)) {
            return null;
        }

        $query = preg_replace_callback(
            '/(([`\'])[\<]*?)?((FROM|TABLE|TABLES LIKE|INTO|UPDATE|JOIN|TABLE IF EXISTS)\s*)?\<((' . $this::TABLE_PATTERN . ')(\.' . $this::COLUMN_PATTERN . ')?)\>([^,]*?\2)?/',
            function ($matches) {
                if (!empty($matches[2]) && isset($matches[8])) {
                    return $matches[0];
                }

                if (!empty($matches[4])) {
                    return $matches[1] . $matches[4] . ' ' . $this->tableQuote($matches[5]);
                }

                return $matches[1] . $this->columnQuote($matches[5]);
            },
            $raw->value
        );

        $rawMap = $raw->map;

        if (!empty($rawMap)) {
            foreach ($rawMap as $key => $value) {
                $map[$key] = $this->typeMap($value, gettype($value));
            }
        }

        return $query;
    }

    /**
     * Quote table name for use in a query.
     *
     * @param string $table
     * @return string
     */
    protected function tableQuote(string $table): string
    {
        if (preg_match("/^" . $this::TABLE_PATTERN . "$/u", $table)) {
            return '"' . $this->prefix . $table . '"';
        }

        throw new InvalidArgumentException("Incorrect table name: {$table}.");
    }

    /**
     * Quote column name for use in a query.
     *
     * @param string $column
     * @return string
     */
    protected function columnQuote(string $column): string
    {
        if (preg_match("/^" . $this::TABLE_PATTERN . "(\.?" . $this::ALIAS_PATTERN . ")?$/u", $column)) {
            return str_contains($column, '.') ?
                '"' . $this->prefix . str_replace('.', '"."', $column) . '"' :
                '"' . $column . '"';
        }

        throw new InvalidArgumentException("Incorrect column name: {$column}.");
    }

    /**
     * Mapping the type name as PDO data type.
     *
     * @param mixed $value
     * @param string $type
     * @return array
     */
    protected function typeMap(mixed $value, string $type): array
    {
        $map = [
            'NULL'     => PDO::PARAM_NULL,
            'integer'  => PDO::PARAM_INT,
            'double'   => PDO::PARAM_STR,
            'boolean'  => PDO::PARAM_BOOL,
            'string'   => PDO::PARAM_STR,
            'object'   => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB,
        ];

        if ($type === 'boolean') {
            $value = ($value ? '1' : '0');
        } elseif ($type === 'NULL') {
            $value = null;
        }

        return [$value, $map[$type]];
    }

    /**
     * Build the statement part for the column stack.
     *
     * @param array|string $columns
     * @param array        $map
     * @param bool         $root
     * @param bool         $isJoin
     * @return string
     */
    protected function columnPush(array|string &$columns, array &$map, bool $root, bool $isJoin = false): string
    {
        if ($columns === '*') {
            return $columns;
        }

        $stack = [];
        $hasDistinct = false;

        if (is_string($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $key => $value) {
            $isIntKey = is_int($key);
            $isArrayValue = is_array($value);

            if (!$isIntKey && $isArrayValue && $root && count(array_keys($columns)) === 1) {
                $stack[] = $this->columnQuote($key);
                $stack[] = $this->columnPush($value, $map, false, $isJoin);
            } elseif ($isArrayValue) {
                $stack[] = $this->columnPush($value, $map, false, $isJoin);
            } elseif (!$isIntKey && $raw = $this->buildRaw($value, $map)) {
                preg_match("/(?<column>" . $this::COLUMN_PATTERN . ")(\s*\[(?<type>(String|Bool|Int|Number))\])?/u", $key, $match);
                $stack[] = "{$raw} AS {$this->columnQuote($match['column'])}";
            } elseif ($isIntKey && is_string($value)) {
                if ($isJoin && str_contains($value, '*')) {
                    throw new InvalidArgumentException('Cannot use table.* to select all columns while joining table.');
                }

                preg_match("/(?<column>" . $this::COLUMN_PATTERN . ")(?:\s*\((?<alias>" . $this::ALIAS_PATTERN . ")\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/u", $value, $match);

                if (!empty($match['alias'])) {
                    $columnString = "{$this->columnQuote($match['column'])} AS {$this->columnQuote($match['alias'])}";
                    $columns[$key] = $match['alias'];

                    if (!empty($match['type'])) {
                        $columns[$key] .= ' [' . $match['type'] . ']';
                    }
                } else {
                    $columnString = $this->columnQuote($match['column']);
                }

                if (!$hasDistinct && str_starts_with($value, '@')) {
                    $columnString = 'DISTINCT ' . $columnString;
                    $hasDistinct = true;
                    array_unshift($stack, $columnString);

                    continue;
                }

                $stack[] = $columnString;
            }
        }

        return implode(',', $stack);
    }

    /**
     * Implode the Where conditions.
     *
     * @param array $data
     * @param array $map
     * @param string $conjunctor
     * @return string
     */
    protected function dataImplode(array $data, array &$map, string $conjunctor): string
    {
        $stack = [];

        foreach ($data as $key => $value) {
            $type = gettype($value);

            if (
                $type === 'array' &&
                preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relationMatch)
            ) {
                $stack[] = '(' . $this->dataImplode($value, $map, ' ' . $relationMatch[1]) . ')';
                continue;
            }

            $mapKey = $this->mapKey();
            $isIndex = is_int($key);

            preg_match(
                "/(?<column>" . $this::COLUMN_PATTERN . ")(\[(?<operator>.*)\])?(?<comparison>" . $this::COLUMN_PATTERN . ")?/u",
                $isIndex ? $value : $key,
                $match
            );

            $column = $this->columnQuote($match['column']);
            $operator = $match['operator'] ?? null;

            if ($isIndex && isset($match['comparison']) && in_array($operator, ['>', '>=', '<', '<=', '=', '!='])) {
                $stack[] = "{$column} {$operator} " . $this->columnQuote($match['comparison']);
                continue;
            }

            if ($operator && $operator !== '=') {
                if (in_array($operator, ['>', '>=', '<', '<='])) {
                    $condition = "{$column} {$operator} ";

                    if (is_numeric($value)) {
                        $condition .= $mapKey;
                        $map[$mapKey] = [$value, is_float($value) ? PDO::PARAM_STR : PDO::PARAM_INT];
                    } elseif ($raw = $this->buildRaw($value, $map)) {
                        $condition .= $raw;
                    } else {
                        $condition .= $mapKey;
                        $map[$mapKey] = [$value, PDO::PARAM_STR];
                    }

                    $stack[] = $condition;
                } elseif ($operator === '!') {
                    switch ($type) {
                        case 'NULL':
                            $stack[] = $column . ' IS NOT NULL';
                            break;

                        case 'array':
                            $values = [];

                            foreach ($value as $index => $item) {
                                if ($raw = $this->buildRaw($item, $map)) {
                                    $values[] = $raw;
                                } else {
                                    $stackKey = $mapKey . $index . '_i';

                                    $values[] = $stackKey;
                                    $map[$stackKey] = $this->typeMap($item, gettype($item));
                                }
                            }

                            $stack[] = $column . ' NOT IN (' . implode(', ', $values) . ')';
                            break;

                        case 'object':
                            if ($raw = $this->buildRaw($value, $map)) {
                                $stack[] = "{$column} != {$raw}";
                            }
                            break;

                        case 'integer':
                        case 'double':
                        case 'boolean':
                        case 'string':
                            $stack[] = "{$column} != {$mapKey}";
                            $map[$mapKey] = $this->typeMap($value, $type);
                            break;
                    }
                } elseif ($operator === '~' || $operator === '!~') {
                    if ($type !== 'array') {
                        $value = [$value];
                    }

                    $connector = ' OR ';
                    $data = array_values($value);

                    if (is_array($data[0])) {
                        if (isset($value['AND']) || isset($value['OR'])) {
                            $connector = ' ' . array_keys($value)[0] . ' ';
                            $value = $data[0];
                        }
                    }

                    $likeClauses = [];

                    foreach ($value as $index => $item) {
                        $likeKey = "{$mapKey}_{$index}_i";
                        $item = strval($item);

                        if (!preg_match('/((?<!\\\)\[.+(?<!\\\)\]|(?<!\\\)[\*\?\!\%#^_]|%.+|.+%)/', $item)) {
                            $item = '%' . $item . '%';
                        }

                        $likeClauses[] = $column . ($operator === '!~' ? ' NOT' : '') . " LIKE {$likeKey}";
                        $map[$likeKey] = [$item, PDO::PARAM_STR];
                    }

                    $stack[] = '(' . implode($connector, $likeClauses) . ')';
                } elseif ($operator === '<>' || $operator === '><') {
                    if ($type === 'array') {
                        if ($operator === '><') {
                            $column .= ' NOT';
                        }

                        if ($this->isRaw($value[0]) && $this->isRaw($value[1])) {
                            $stack[] = "({$column} BETWEEN {$this->buildRaw($value[0], $map)} AND {$this->buildRaw($value[1], $map)})";
                        } else {
                            $stack[] = "({$column} BETWEEN {$mapKey}a AND {$mapKey}b)";
                            $dataType = (is_numeric($value[0]) && is_numeric($value[1])) ? PDO::PARAM_INT : PDO::PARAM_STR;

                            $map[$mapKey . 'a'] = [$value[0], $dataType];
                            $map[$mapKey . 'b'] = [$value[1], $dataType];
                        }
                    }
                } elseif ($operator === 'REGEXP') {
                    $stack[] = "{$column} REGEXP {$mapKey}";
                    $map[$mapKey] = [$value, PDO::PARAM_STR];
                } else {
                    throw new InvalidArgumentException("Invalid operator [{$operator}] for column {$column} supplied.");
                }

                continue;
            }

            switch ($type) {
                case 'NULL':
                    $stack[] = $column . ' IS NULL';
                    break;

                case 'array':
                    $values = [];

                    foreach ($value as $index => $item) {
                        if ($raw = $this->buildRaw($item, $map)) {
                            $values[] = $raw;
                        } else {
                            $stackKey = $mapKey . $index . '_i';

                            $values[] = $stackKey;
                            $map[$stackKey] = $this->typeMap($item, gettype($item));
                        }
                    }

                    $stack[] = $column . ' IN (' . implode(', ', $values) . ')';
                    break;

                case 'object':
                    if ($raw = $this->buildRaw($value, $map)) {
                        $stack[] = "{$column} = {$raw}";
                    }
                    break;

                case 'integer':
                case 'double':
                case 'boolean':
                case 'string':
                    $stack[] = "{$column} = {$mapKey}";
                    $map[$mapKey] = $this->typeMap($value, $type);
                    break;
            }
        }

        return implode($conjunctor . ' ', $stack);
    }

    /**
     * Build the where clause.
     *
     * @param null|array $where
     * @param array      $map
     * @return string
     */
    protected function whereClause(?array $where, array &$map): string
    {
        $clause = '';

        if (is_array($where)) {
            $conditions = array_diff_key($where, array_flip(
                ['GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH']
            ));

            if (!empty($conditions)) {
                $clause = ' WHERE ' . $this->dataImplode($conditions, $map, ' AND');
            }

            if (isset($where['MATCH']) && $this->type === 'mysql') {
                $match = $where['MATCH'];

                if (is_array($match) && isset($match['columns'], $match['keyword'])) {
                    $mode = '';

                    $options = [
                        'natural'       => 'IN NATURAL LANGUAGE MODE',
                        'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
                        'boolean'       => 'IN BOOLEAN MODE',
                        'query'         => 'WITH QUERY EXPANSION',
                    ];

                    if (isset($match['mode'], $options[$match['mode']])) {
                        $mode = ' ' . $options[$match['mode']];
                    }

                    $columns = implode(', ', array_map([$this, 'columnQuote'], $match['columns']));
                    $mapKey = $this->mapKey();
                    $map[$mapKey] = [$match['keyword'], PDO::PARAM_STR];
                    $clause .= ($clause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $mapKey . $mode . ')';
                }
            }

            if (isset($where['GROUP'])) {
                $group = $where['GROUP'];

                if (is_array($group)) {
                    $stack = [];

                    foreach ($group as $column => $value) {
                        $stack[] = $this->columnQuote($value);
                    }

                    $clause .= ' GROUP BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($group, $map)) {
                    $clause .= ' GROUP BY ' . $raw;
                } else {
                    $clause .= ' GROUP BY ' . $this->columnQuote($group);
                }
            }

            if (isset($where['HAVING'])) {
                $having = $where['HAVING'];

                if ($raw = $this->buildRaw($having, $map)) {
                    $clause .= ' HAVING ' . $raw;
                } else {
                    $clause .= ' HAVING ' . $this->dataImplode($having, $map, ' AND');
                }
            }

            if (isset($where['ORDER'])) {
                $order = $where['ORDER'];

                if (is_array($order)) {
                    $stack = [];

                    foreach ($order as $column => $value) {
                        if (is_array($value)) {
                            $valueStack = [];

                            foreach ($value as $item) {
                                $valueStack[] = is_int($item) ? $item : $this->quote($item);
                            }

                            $valueString = implode(',', $valueStack);
                            $stack[] = "FIELD({$this->columnQuote($column)}, {$valueString})";
                        } elseif ($value === 'ASC' || $value === 'DESC') {
                            $stack[] = $this->columnQuote($column) . ' ' . $value;
                        } elseif (is_int($column)) {
                            $stack[] = $this->columnQuote($value);
                        }
                    }

                    $clause .= ' ORDER BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($order, $map)) {
                    $clause .= ' ORDER BY ' . $raw;
                } else {
                    $clause .= ' ORDER BY ' . $this->columnQuote($order);
                }
            }

            if (isset($where['LIMIT'])) {
                $limit = $where['LIMIT'];

                if (in_array($this->type, ['oracle', 'mssql'])) {
                    if ($this->type === 'mssql' && !isset($where['ORDER'])) {
                        $clause .= ' ORDER BY (SELECT 0)';
                    }

                    if (is_numeric($limit)) {
                        $limit = [0, $limit];
                    }

                    if (
                        is_array($limit) &&
                        is_numeric($limit[0]) &&
                        is_numeric($limit[1])
                    ) {
                        $clause .= " OFFSET {$limit[0]} ROWS FETCH NEXT {$limit[1]} ROWS ONLY";
                    }
                } else {
                    if (is_numeric($limit)) {
                        $clause .= ' LIMIT ' . $limit;
                    } elseif (
                        is_array($limit) &&
                        is_numeric($limit[0]) &&
                        is_numeric($limit[1])
                    ) {
                        $clause .= " LIMIT {$limit[1]} OFFSET {$limit[0]}";
                    }
                }
            }
        } elseif ($raw = $this->buildRaw($where, $map)) {
            $clause .= ' ' . $raw;
        }

        return $clause;
    }

    /**
     * Build statement for the select query.
     *
     * @param string            $table
     * @param array             $map
     * @param array|string      $join
     * @param null|array|string $columns
     * @param null|array        $where
     * @param null|string       $columnFn
     * @return string
     */
    protected function selectContext(
        string $table,
        array &$map,
        array|string $join,
        array|string &$columns = null,
        ?array $where = null,
        ?string $columnFn = null
    ): string
    {
        preg_match("/(?<table>" . $this::TABLE_PATTERN . ")\s*\((?<alias>" . $this::ALIAS_PATTERN . ")\)/u", $table, $tableMatch);

        if (isset($tableMatch['table'], $tableMatch['alias'])) {
            $table = $this->tableQuote($tableMatch['table']);
            $tableAlias = $this->tableQuote($tableMatch['alias']);
            $tableQuery = "{$table} AS {$tableAlias}";
        } else {
            $table = $this->tableQuote($table);
            $tableQuery = $table;
        }

        $isJoin = $this->isJoin($join);

        if ($isJoin) {
            $tableQuery .= ' ' . $this->buildJoin($tableAlias ?? $table, $join, $map);
        } else {
            if (is_null($columns)) {
                if (
                    !is_null($where) ||
                    (is_array($join) && isset($columnFn))
                ) {
                    $where = $join;
                    $columns = null;
                } else {
                    $where = null;
                    $columns = $join;
                }
            } else {
                $where = $columns;
                $columns = $join;
            }
        }

        if (isset($columnFn)) {
            if ($columnFn === 1) {
                $column = '1';

                if (is_null($where)) {
                    $where = $columns;
                }
            } elseif ($raw = $this->buildRaw($columnFn, $map)) {
                $column = $raw;
            } else {
                if (empty($columns) || $this->isRaw($columns)) {
                    $columns = '*';
                    $where = $join;
                }

                $column = $columnFn . '(' . $this->columnPush($columns, $map, true) . ')';
            }
        } else {
            $column = $this->columnPush($columns, $map, true, $isJoin);
        }

        return 'SELECT ' . $column . ' FROM ' . $tableQuery . $this->whereClause($where, $map);
    }

    /**
     * Determine the array with join syntax.
     *
     * @param mixed $join
     * @return bool
     */
    protected function isJoin(mixed $join): bool
    {
        if (!is_array($join)) {
            return false;
        }

        $keys = array_keys($join);

        if (is_string($keys[0] ?? null) && str_starts_with($keys[0], '[')) {
            return true;
        }

        return false;
    }

    /**
     * Build the join statement.
     *
     * @param string $table
     * @param array $join
     * @param array $map
     * @return string
     */
    protected function buildJoin(string $table, array $join, array &$map): string
    {
        $tableJoin = [];
        $type = [
            '>'  => 'LEFT',
            '<'  => 'RIGHT',
            '<>' => 'FULL',
            '><' => 'INNER',
        ];

        foreach ($join as $subtable => $relation) {
            preg_match("/(\[(?<join>\<\>?|\>\<?)\])?(?<table>" . $this::TABLE_PATTERN . ")\s?(\((?<alias>" . $this::ALIAS_PATTERN . ")\))?/u", $subtable, $match);

            if ($match['join'] === '' || $match['table'] === '') {
                continue;
            }

            if (is_string($relation)) {
                $relation = 'USING ("' . $relation . '")';
            } elseif (is_array($relation)) {
                // For ['column1', 'column2']
                if (isset($relation[0])) {
                    $relation = 'USING ("' . implode('", "', $relation) . '")';
                } else {
                    $joins = [];

                    foreach ($relation as $key => $value) {
                        if ($key === 'AND' && is_array($value)) {
                            $joins[] = $this->dataImplode($value, $map, ' AND');
                            continue;
                        }

                        $joins[] = (
                            strpos($key, '.') > 0 ?
                                // For ['tableB.column' => 'column']
                                $this->columnQuote($key) :

                                // For ['column1' => 'column2']
                                $table . '.' . $this->columnQuote($key)
                            ) .
                            ' = ' .
                            $this->tableQuote($match['alias'] ?? $match['table']) . '.' . $this->columnQuote($value);
                    }

                    $relation = 'ON ' . implode(' AND ', $joins);
                }
            } elseif ($raw = $this->buildRaw($relation, $map)) {
                $relation = $raw;
            }

            $tableName = $this->tableQuote($match['table']);

            if (isset($match['alias'])) {
                $tableName .= ' AS ' . $this->tableQuote($match['alias']);
            }

            $tableJoin[] = $type[$match['join']] . " JOIN {$tableName} {$relation}";
        }

        return implode(' ', $tableJoin);
    }

    /**
     * Mapping columns for the stack.
     *
     * @param array|string $columns
     * @param array $stack
     * @param bool $root
     * @return array
     */
    protected function columnMap($columns, array &$stack, bool $root): array
    {
        if ($columns === '*') {
            return $stack;
        }

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                preg_match("/(" . $this::TABLE_PATTERN . "\.)?(?<column>" . $this::COLUMN_PATTERN . ")(?:\s*\((?<alias>" . $this::ALIAS_PATTERN . ")\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/u", $value, $keyMatch);

                $columnKey = !empty($keyMatch['alias']) ?
                    $keyMatch['alias'] :
                    $keyMatch['column'];

                $stack[$value] = isset($keyMatch['type']) ?
                    [$columnKey, $keyMatch['type']] :
                    [$columnKey];
            } elseif ($this->isRaw($value)) {
                preg_match("/(" . $this::TABLE_PATTERN . "\.)?(?<column>" . $this::COLUMN_PATTERN . ")(\s*\[(?<type>(String|Bool|Int|Number))\])?/u", $key, $keyMatch);
                $columnKey = $keyMatch['column'];

                $stack[$key] = isset($keyMatch['type']) ?
                    [$columnKey, $keyMatch['type']] :
                    [$columnKey];
            } elseif (!is_int($key) && is_array($value)) {
                if ($root && count(array_keys($columns)) === 1) {
                    $stack[$key] = [$key, 'String'];
                }

                $this->columnMap($value, $stack, false);
            }
        }

        return $stack;
    }

    /**
     * Mapping the data from the table.
     *
     * @param array      $data
     * @param array      $columns
     * @param array      $columnMap
     * @param array      $stack
     * @param bool       $root
     * @param null|array $result
     * @return void
     */
    protected function dataMap(
        array $data,
        array $columns,
        array $columnMap,
        array &$stack,
        bool $root,
        ?array &$result = null
    ): void
    {
        if ($root) {
            $columnsKey = array_keys($columns);

            if (count($columnsKey) === 1 && is_array($columns[$columnsKey[0]])) {
                $indexKey = array_keys($columns)[0];
                $dataKey = preg_replace("/^" . $this::COLUMN_PATTERN . "\./u", '', $indexKey);
                $currentStack = [];

                foreach ($data as $item) {
                    $this->dataMap($data, $columns[$indexKey], $columnMap, $currentStack, false, $result);
                    $index = $data[$dataKey];

                    if (isset($result)) {
                        $result[$index] = $currentStack;
                    } else {
                        $stack[$index] = $currentStack;
                    }
                }
            } else {
                $currentStack = [];
                $this->dataMap($data, $columns, $columnMap, $currentStack, false, $result);

                if (isset($result)) {
                    $result[] = $currentStack;
                } else {
                    $stack = $currentStack;
                }
            }

            return;
        }

        foreach ($columns as $key => $value) {
            $isRaw = $this->isRaw($value);

            if (is_int($key) || $isRaw) {
                $map = $columnMap[$isRaw ? $key : $value];
                $columnKey = $map[0];
                $item = $data[$columnKey];

                if (isset($map[1])) {
                    if ($isRaw && in_array($map[1], ['Object', 'JSON'])) {
                        continue;
                    }

                    if (is_null($item)) {
                        $stack[$columnKey] = null;
                        continue;
                    }

                    $stack[$columnKey] = match ($map[1]) {
                        'Number' => (float) $item,
                        'Int'    => (int) $item,
                        'Bool'   => (bool) $item,
                        'Object' => unserialize($item),
                        'JSON'   => json_decode($item, true),
                        'String' => (string) $item,
                        default  => $item,
                    };
                } else {
                    $stack[$columnKey] = $item;
                }
            } else {
                $currentStack = [];
                $this->dataMap($data, $value, $columnMap, $currentStack, false, $result);

                $stack[$key] = $currentStack;
            }
        }
    }

    /**
     * Build and execute returning query.
     *
     * @param string $query
     * @param array  $map
     * @param array  $data
     * @return PDOStatement|null
     */
    protected function returningQuery(string $query, array &$map, array &$data): ?PDOStatement
    {
        $returnColumns = array_map(fn ($value) => $value[0], $data);

        $query .= ' RETURNING ' .
            implode(', ', array_map([$this, 'columnQuote'], $returnColumns)) .
            ' INTO ' .
            implode(', ', array_keys($data));

        return $this->exec($query, $map, function ($statement) use (&$data) {
            foreach ($data as $key => $return) {
                if (isset($return[3])) {
                    $statement->bindParam($key, $data[$key][1], $return[2], $return[3]);
                } else {
                    $statement->bindParam($key, $data[$key][1], $return[2]);
                }
            }
        });
    }

    /**
     * Build for the aggregate function.
     *
     * @param string      $type
     * @param string      $table
     * @param null|array  $join
     * @param null|string $column
     * @param null|array  $where
     * @return null|string
     */
    protected function aggregate(string $type, string $table, array $join = null, string $column = null, array $where = null): ?string
    {
        $map = [];

        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, $type), $map);

        if (!$this->statement) {
            return null;
        }

        return (string) $query->fetchColumn();
    }
}
