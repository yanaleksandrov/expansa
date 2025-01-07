<?php

declare(strict_types=1);

namespace Expansa\Cache;

trait Traits
{
    /**
     * DB table name.
     *
     * @var string
     */
    protected static string $table = 'cache';

    /**
     * Holds the cached data.
     *
     * @var array
     */
    protected static array $cache = [];

    /**
     * Holds the locked keys.
     *
     * @var array
     */
    protected static array $locks = [];
}
