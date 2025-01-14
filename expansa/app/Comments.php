<?php

declare(strict_types=1);

namespace app;

/**
 * Core class for managing comments.
 */
final class Comments
{
    /**
     * DB table name.
     *
     * @var string
     */
    public static string $table = 'comments';

    /**
     * @param string $string
     */
    public static function add(string $string): void
    {
    }
}
