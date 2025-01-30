<?php

declare(strict_types=1);

namespace App;

/**
 * Core class for managing taxonomies.
 */
final class Taxonomy
{
    /**
     * DB table name.
     *
     * @var string
     */
    public static string $table = 'term_taxonomy';

    /**
     *
     *
     * @param string $taxonomy
     * @param string $object_type
     * @param array $args
     */
    public static function register(string $taxonomy, string $object_type, array $args = [])
    {
    }

    /**
     *
     *
     * @param string $taxonomy
     */
    public static function unregister(string $taxonomy)
    {
    }

    /**
     * Determines whether the taxonomy object is hierarchical.
     *
     * Checks to make sure that the taxonomy is an object first. Then Gets the
     * object, and finally returns the hierarchical value in the object.
     *
     * A false return value might also mean that the taxonomy does not exist.
     *
     * @param string $taxonomy Name of taxonomy object.
     * @return  bool           Whether the taxonomy is hierarchical.
     */
    public static function isHierarchical(string $taxonomy): bool
    {
    }
}
