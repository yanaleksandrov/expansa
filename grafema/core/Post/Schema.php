<?php
namespace Grafema\Post;

use Grafema\Db;

/**
 *
 *
 * @since 2025.1
 */
final class Schema {

	/**
	 * Get query for create new table into database.
	 *
	 * @param string $table
	 * @return void
	 * @since 2025.1
	 */
	public static function migrate( string $table ): void {
		$tableName      = (new Db\Handler)->getTableName( $table );
		$charsetCollate = (new Db\Handler)->getCharsetCollate();

		Db::query(
			"
			CREATE TABLE IF NOT EXISTS {$tableName} (
				id          int          UNSIGNED NOT NULL AUTO_INCREMENT,
				title       text         NOT NULL DEFAULT '',
				content     longtext     NOT NULL DEFAULT '',
				author      smallint     UNSIGNED NOT NULL DEFAULT '0',
				comments    smallint     UNSIGNED NOT NULL DEFAULT '0',
				views       mediumint    UNSIGNED NOT NULL DEFAULT '0',
				status      varchar(255) NOT NULL DEFAULT 'draft',
				discussion  varchar(255) NOT NULL DEFAULT 'open',
				password    varchar(255) NOT NULL DEFAULT '',
				parent      int          UNSIGNED NOT NULL DEFAULT '0',
				position    mediumint    UNSIGNED NOT NULL DEFAULT '0',
				created_at  datetime     NOT NULL DEFAULT NOW(),
				updated_at  datetime     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
				PRIMARY KEY (ID),
				KEY parent (parent),
				KEY author (author),
				FULLTEXT KEY content (title,content)
			) ENGINE=InnoDB {$charsetCollate}
			COMMENT='post-type';"
		)->fetchAll();
	}
}
