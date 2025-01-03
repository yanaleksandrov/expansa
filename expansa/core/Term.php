<?php

declare(strict_types=1);

namespace Expansa;

/**
 * Core class for managing plugins.
 */
final class Term
{
    /**
     * @param string $term
     * @param string $object_type
     * @param array $args
     */
    public static function register(string $term, string $object_type, array $args = [])
    {
    }

    /**
     * @param string $term
     */
    public static function unregister(string $term)
    {
    }
}
