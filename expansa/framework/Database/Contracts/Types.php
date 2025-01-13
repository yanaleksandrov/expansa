<?php

declare(strict_types=1);

namespace Expansa\Database\Contracts;

interface Types
{
    public const BIT = 'bit';
    public const TINY_INTEGER = 'tinyint';
    public const SMALL_INTEGER = 'smallint';
    public const MEDIUM_INTEGER = 'mediumint';
    public const INTEGER = 'int';
    public const BIG_INTEGER = 'bigint';
    public const DECIMAL = 'decimal';
    public const FLOAT = 'float';
    public const DOUBLE = 'double';
    public const BINARY = 'binary';
    public const VARBINARY = 'varbinary';
    public const CHAR = 'char';
    public const STRING = 'varchar';
    public const BOOLEAN = 'tinyint(1)';
    public const DATE = 'date';
    public const TIME = 'time';
    public const TIMESTAMP = 'timestamp';
    public const DATETIME = 'datetime';
    public const TINY_TEXT = 'tinytext';
    public const MEDIUM_TEXT = 'mediumtext';
    public const TEXT = 'text';
    public const LONG_TEXT = 'longtext';
    public const TINY_BLOB = 'tinyblob';
    public const MEDIUM_BLOB = 'mediumblob';
    public const BLOB = 'blob';
    public const LONG_BLOB = 'longblob';
    public const UUID = 'char(36)';
    public const JSON = 'text';
    public const ENUM = 'enum';
    public const SET = 'set';
    public const POINT = 'point';
    public const LINE = 'linestring';
    public const POLYGON = 'polygon';
}
