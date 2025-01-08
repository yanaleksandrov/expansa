<?php

declare(strict_types=1);

namespace Expansa\Database\Drivers\SQLite;

use Expansa\Database\Abstracts\AbstractConnectionBase;
use Expansa\Database\Contracts\DatabaseException;

class Connection extends AbstractConnectionBase
{
    public function __construct($pdo, array $config = [])
    {
        parent::__construct($pdo, $config);

        if (isset($config['foreign_keys']) && $config['foreign_keys'] === true) {
            $this->getSchemaBuilder()->enableForeignKeys();
        } else {
            $this->getSchemaBuilder()->disableForeignKeys();
        }
    }

    public function getSchema()
    {
        if (empty($this->config['schema'])) {
            throw new DatabaseException("For connection [%s] schema not configured.");
        }

        return $this->config['schema'];
    }

    public function useSchemaGrammar(): static
    {
        $this->schemaGrammar = new SchemaGrammar();
        $this->schemaGrammar->setTablePrefix($this->tablePrefix);

        return $this;
    }

    public function getSchemaBuilder(): SchemaBuilder
    {
        if (is_null($this->schemaGrammar)) {
            $this->useSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    public function useQueryGrammar(): static
    {
        $this->queryGrammar = new QueryGrammar();
        $this->queryGrammar->setTablePrefix($this->tablePrefix);

        return $this;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        if (is_null($this->queryGrammar)) {
            $this->useQueryGrammar();
        }

        return new QueryBuilder($this, $this->queryGrammar);
    }
}
