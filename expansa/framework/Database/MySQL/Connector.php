<?php declare(strict_types=1);

namespace Expansa\Database\MySQL;

use Expansa\Database\Abstracts\AbstractConnectorBase;
use PDO;

class Connector extends AbstractConnectorBase
{
    protected array $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        //PDO::ATTR_TIMEOUT => 1
    ];
}