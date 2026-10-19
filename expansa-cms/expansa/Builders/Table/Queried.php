<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

/**
 * Table that reads its rows from the database by pages, so that the filters and the sorting cover the whole table,
 * not the rows loaded in PHP: data() returns only the rows of the current page, read with Table::paged(),
 * and count() the number of the rows that match the filters, read with Table::filtered().
 *
 * ```php
 * public function data(): array
 * {
 *     return Db::select('comments', '*', $this->paged(['deleted_at' => null], ['id' => 'DESC'])) ?? [];
 * }
 *
 * public function count(): int
 * {
 *     return (int) Db::count('comments', $this->filtered(['deleted_at' => null]));
 * }
 * ```
 *
 * @package Expansa\Builders\Table
 */
interface Queried
{
    /**
     * Count the rows that match the filters of the table on all pages.
     *
     * @return int
     */
    public function count(): int;
}
