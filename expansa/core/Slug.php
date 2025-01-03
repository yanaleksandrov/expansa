<?php

declare(strict_types=1);

namespace Expansa;

/**
 * @since 2025.1
 */
final class Slug {

	/**
	 * DB table name.
	 *
	 * @var string
	 *
	 * @since 2025.1
	 */
	private static string $table = 'slugs';

	/**
	 * Add new slug.
	 *
	 * @param int $entityId
	 * @param string $entityTable
	 * @param string $slug
	 * @param string $locale
	 * @return string
	 *
	 * @since 2025.1
	 */
	public static function add( int $entityId, string $entityTable, string $slug, string $locale = '' ): string {
		$slug = Sanitizer::slug( $slug );

		try {
			return Db::insert(
				self::$table,
				[
					'entity_id'    => $entityId,
					'entity_table' => $entityTable,
					'slug'         => $slug,
					'locale'       => $locale,
				]
			)->rowCount() > 0;
		} catch ( \Exception $e ) {
			$slugs = Db::select( self::$table, 'slug', [
				'AND' => [
					'slug[REGEXP]' => sprintf( '^%s(-[1-9][0-9]*)?$', preg_quote( $slug, '/' ) ),
				]
			] );

			// checking the uniqueness of a slug, add a numeric suffix if found
			$maxSuffix = 0;
			if ( count( $slugs ) === 1 && $slugs[0] === $slug ) {
				$maxSuffix = 1;
			} else {
				$slugs = array_diff( $slugs, [ $slug ] );
				if ( $slugs ) {
					$maxSuffix = max( array_map( fn( $item ) => (int) substr( strrchr( $item, '-' ), 1 ), $slugs ) );
				}
			}

			if ( $maxSuffix > 0 ) {
				$slug = sprintf( '%s-%d', $slug, $maxSuffix + 1 );
			}
		}

		$isInsert = Db::insert(
			self::$table,
			[
				'entity_id'    => $entityId,
				'entity_table' => $entityTable,
				'slug'         => $slug,
				'locale'       => $locale,
			]
		)->rowCount() > 0;

		return $isInsert ? $slug : '';
	}

	/**
	 * Get entity slug.
	 *
	 * @param int $entityId
	 * @param string $entityTable
	 * @return string
	 */
	public static function getByEntity( int $entityId, string $entityTable ): string {
		return Db::get( self::$table, 'slug', [ 'entity_id' => $entityId, 'entity_table' => $entityTable ] ) ?? '';
	}

	/**
	 * Get data by slug.
	 *
	 * @param string $slug
	 * @param string $locale
	 * @return mixed
	 *
	 * @since 2025.1
	 */
	public static function get( string $slug, string $locale = '' ): mixed {
		return Db::get( self::$table, '*', [ 'slug' => $slug, 'locale' => $locale ] );
	}

	/**
	 * @param string $slug
	 * @param string $newSlug
	 * @param string $locale
	 * @return bool
	 *
	 * @since 2025.1
	 */
	public static function update( string $slug, string $newSlug, string $locale = '' ): bool {
		return Db::update( self::$table, [ 'slug' => $newSlug ], [ 'slug[=]' => $slug ] )->rowCount() === 1;
	}

	/**
	 * @param string $value
	 * @param string $by
	 * @return bool
	 *
	 * @since 2025.1
	 */
	public static function delete( string $value, string $by = 'slug' ): bool {
		if ( ! in_array( $by, [ 'uuid', 'entity_id', 'entity_table', 'slug', 'locale' ], true ) ) {
			return false;
		}
		return Db::delete( self::$table, [ $by => $value ] )->rowCount() > 0;
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
				id           BIGINT(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
			    uuid         CHAR(13)     NOT NULL,
			    entity_id    BIGINT(20)   UNSIGNED NOT NULL,
				entity_table VARCHAR(255) NOT NULL,
                slug         VARCHAR(255) NOT NULL UNIQUE,
			    locale       VARCHAR(100) NOT NULL DEFAULT '',

				PRIMARY KEY (id),

			    UNIQUE KEY unique_uuid (uuid),
			    UNIQUE KEY unique_slug_locale (slug, locale),

                INDEX idx_entity (entity_id, entity_table)
			) ENGINE=InnoDB {$charsetCollate};

			CREATE TRIGGER before_insert_{$tableName}
				BEFORE INSERT ON {$tableName}
				FOR EACH ROW
					BEGIN
					    IF NEW.uuid IS NULL THEN
					        SET NEW.uuid = LOWER(CONV(UUID_SHORT(), 10, 36));
					    END IF;
					END;"
		)->fetchAll();
	}
}
