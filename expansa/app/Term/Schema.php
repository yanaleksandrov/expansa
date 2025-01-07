<?php

declare(strict_types=1);

namespace app\Term;

use app\Field;
use Expansa\Db;

class Schema
{
    /**
     * DB table name.
     *
     * @var string
     */
    public static string $table = 'term';

    /**
     * Create new table into database.
     */
    public static function migrate(): void
    {
        $indexLength    = EX_DB_MAX_INDEX_LENGTH;
        $tableName      = (new Db\Handler())->getTableName(self::$table);
        $charsetCollate = (new Db\Handler())->getCharsetCollate();

        Db::query(
            "
			CREATE TABLE IF NOT EXISTS {$tableName} (
				term_id    bigint(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
				name       varchar(200) NOT NULL default '',
				slug       varchar(200) NOT NULL default '',
				term_group bigint(10)   NOT NULL default 0,
				PRIMARY    KEY (term_id),
				KEY slug (slug({$indexLength})),
				KEY name (name({$indexLength}))
			) ENGINE=InnoDB $charsetCollate;"
        )->fetchAll();

        Field\Schema::migrate($tableName, 'term');
    }
}
