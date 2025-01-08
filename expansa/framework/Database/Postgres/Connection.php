<?php declare(strict_types=1);

namespace Expansa\Database\Postgres;

use Expansa\Database\Contracts\DatabaseException;
use Expansa\Database\Postgres\Query\Builder as QueryBuilder;
use Expansa\Database\Postgres\Query\Grammar as QueryGrammar;
use Expansa\Database\Postgres\Schema\Builder as SchemaBuilder;
use Expansa\Database\Postgres\Schema\Grammar as SchemaGrammar;
use Expansa\Database\Abstracts\AbstractConnection;

class Connection extends AbstractConnection
{
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