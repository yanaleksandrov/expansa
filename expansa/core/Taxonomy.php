<?php

namespace Expansa;

use Expansa\Db;

/**
 * Core class for managing taxonomies.
 *
 * @since 2025.1
 */
final class Taxonomy {

	/**
	 * DB table name.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public static string $table = 'term_taxonomy';

	/**
	 *
	 *
	 * @param string $taxonomy
	 * @param string $object_type
	 * @param array $args
	 * @since 2025.1
	 */
	public static function register( string $taxonomy, string $object_type, array $args = [] ) {

	}

	/**
	 *
	 *
	 * @param string $taxonomy
	 * @since 2025.1
	 */
	public static function unregister( string $taxonomy ) {

	}

	/**
	 * Determines whether the taxonomy object is hierarchical.
	 *
	 * Checks to make sure that the taxonomy is an object first. Then Gets the
	 * object, and finally returns the hierarchical value in the object.
	 *
	 * A false return value might also mean that the taxonomy does not exist.
	 *
	 * @since 2025.1
	 *
	 * @param string $taxonomy Name of taxonomy object.
	 * @return  bool           Whether the taxonomy is hierarchical.
	 */
	public static function isHierarchical( string $taxonomy ): bool {

	}

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
				term_taxonomy_id  bigint(20)  UNSIGNED NOT NULL AUTO_INCREMENT,
				term_id           bigint(20)  UNSIGNED NOT NULL DEFAULT 0,
				taxonomy          varchar(32) NOT NULL DEFAULT '',
				description       longtext    NOT NULL,
				count             bigint(20)  NOT NULL DEFAULT 0,
				parent            bigint(20)  UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (term_taxonomy_id),
				UNIQUE KEY term_id_taxonomy (term_id,taxonomy),
				KEY taxonomy (taxonomy)
			) ENGINE=InnoDB $charsetCollate;"
		)->fetchAll();
	}
}
