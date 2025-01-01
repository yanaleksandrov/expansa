<?php
namespace Expansa\Option;

use Expansa\Db;

/**
 *
 *
 * @since 2025.1
 */
class Schema {

	/**
	 * DB table name.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public static string $table = 'options';

	/**
	 * Create new table into database.
	 *
	 * @since 2025.1
	 */
	public static function migrate(): void {
		$tableName      = (new Db\Handler)->getTableName( self::$table );
		$charsetCollate = (new Db\Handler)->getCharsetCollate();

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
