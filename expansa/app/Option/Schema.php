<?php

declare(strict_types=1);

namespace app\Option;

use Expansa\Db;

class Schema
{
    /**
     * DB table name.
     *
     * @var string
     */
    public static string $table = 'options';

    /**
     * Create new table into database.
     */
    public static function migrate(): void
    {
        $tableName      = (new Db\Handler())->getTableName(self::$table);
        $charsetCollate = (new Db\Handler())->getCharsetCollate();

        Db::query(
            "
			CREATE TABLE IF NOT EXISTS {$tableName} (
				`id`    BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
				`key`   VARCHAR(191) NOT NULL DEFAULT '',
				`value` MEDIUMTEXT   NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY `key` (`key`)
			) {$charsetCollate};"
        )->fetchAll();
    }
}
