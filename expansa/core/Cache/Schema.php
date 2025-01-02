<?php

declare(strict_types=1);

namespace Expansa\Cache;

use Expansa\Db;

/**
 * Schema class for managing database schema related operations.
 *
 * @since 2025.1
 */
class Schema
{
    use Traits;

    /**
     * Creates a new table in the database.
     *
     * This method generates the SQL to create a table for storing cache
     * or transient data, with fields for `id`, `key`, `value`, `expiry_at`,
     * and `created_at`. It also sets up indexes for `expiry_at` and `key`.
     */
    public static function migrate(): void
    {
        $tableName      = (new Db\Handler())->getTableName(self::$table);
        $charsetCollate = (new Db\Handler())->getCharsetCollate();

        Db::query(
            "
			CREATE TABLE IF NOT EXISTS {$tableName} (
				`key`        VARCHAR(255) NOT NULL,
				`value`      MEDIUMTEXT   NOT NULL,
				`expiration` INT(11) UNSIGNED NOT NULL,

			    PRIMARY KEY (`key`),

			    INDEX idx_expiration (expiration),
				INDEX idx_key (`key`) 
			) ENGINE=InnoDB {$charsetCollate};"
        )->fetchAll();
    }
}
