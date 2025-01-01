<?php
namespace Grafema\Db;

use Grafema\Sanitizer;

/**
 * Class for handling database operations.
 *
 * This class provides methods for interacting with the database, including retrieving
 * charset and collation settings for table creation queries.
 *
 * @since 2025.1
 */
class Handler {

	/**
	 * Get safe DB table name.
	 *
	 * @param string $table
	 * @return string
	 */
	public function getTableName( string $table ): string
	{
		return GRFM_DB_PREFIX . Sanitizer::tablename( $table );
	}

	/**
	 * Returns the charset and collation string for the database.
	 *
	 * This method checks the constants GRFM_DB_CHARSET and GRFM_DB_COLLATE and generates a string
	 * to be used in SQL queries to set the charset and collation.
	 *
	 * @return string Returns the charset and collation string.
	 *
	 * @since 2025.1
	 */
	public function getCharsetCollate(): string {
		$charsetCollate = '';
		if ( GRFM_DB_CHARSET ) {
			$charsetCollate = 'DEFAULT CHARACTER SET ' . GRFM_DB_CHARSET;
		}
		if ( GRFM_DB_COLLATE ) {
			$charsetCollate .= ' COLLATE ' . GRFM_DB_COLLATE;
		}
		return $charsetCollate;
	}
}
