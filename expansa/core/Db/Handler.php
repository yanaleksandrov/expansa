<?php

declare(strict_types=1);

namespace Expansa\Db;

use Expansa\Sanitizer;

/**
 * Class for handling database operations.
 *
 * This class provides methods for interacting with the database, including retrieving
 * charset and collation settings for table creation queries.
 */
class Handler
{
    /**
     * Get safe DB table name.
     *
     * @param string $table
     * @return string
     */
    public function getTableName(string $table): string
    {
        return EX_DB_PREFIX . Sanitizer::tablename($table);
    }

    /**
     * Returns the charset and collation string for the database.
     *
     * This method checks the constants EX_DB_CHARSET and EX_DB_COLLATE and generates a string
     * to be used in SQL queries to set the charset and collation.
     *
     * @return string Returns the charset and collation string.
     */
    public function getCharsetCollate(): string
    {
        $charsetCollate = '';
        if (EX_DB_CHARSET) {
            $charsetCollate = 'DEFAULT CHARACTER SET ' . EX_DB_CHARSET;
        }
        if (EX_DB_COLLATE) {
            $charsetCollate .= ' COLLATE ' . EX_DB_COLLATE;
        }
        return $charsetCollate;
    }
}
