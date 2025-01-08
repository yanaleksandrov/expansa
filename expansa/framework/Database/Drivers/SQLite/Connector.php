<?php

declare(strict_types=1);

namespace Expansa\Database\Drivers\SQLite;

use Expansa\Database\Abstracts\AbstractConnectorBase;
use Expansa\Database\Contracts\DatabaseException;

class Connector extends AbstractConnectorBase
{
    public function getDsn(): string
    {
        $dsn = "sqlite:";

        if ($this->config['database'] === ':memory:') {
            $dsn .= "memory";
        } else {
            $path = realpath($this->config['database']);

            if ($path === false) {
                throw new DatabaseException('Database "' . $this->config['database'] . '" does not exists.');
            }

            $dsn .= $path;
        }

        return $dsn;
    }
}
