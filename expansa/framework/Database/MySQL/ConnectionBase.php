<?php declare(strict_types=1);

namespace Expansa\Database\MySQL;

use Expansa\Database\Contracts\DatabaseException;
use Expansa\Database\MySQL\Query\Builder as QueryBuilder;
use Expansa\Database\MySQL\Query\Grammar as QueryGrammar;
use Expansa\Database\MySQL\Schema\Builder as SchemaBuilder;
use Expansa\Database\MySQL\Schema\Grammar as SchemaGrammar;
use Expansa\Database\Abstracts\AbstractConnectionBase;

class ConnectionBase extends AbstractConnectionBase
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
        $this->schemaGrammar->setCharset($this->getConfig('charset', 'utf8mb4'));
        $this->schemaGrammar->setCollate($this->getConfig('collate', 'utf8mb4_unicode_ci'));
        $this->schemaGrammar->setEngine($this->getConfig('engine', 'InnoDB'));

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