<?php

declare(strict_types=1);

namespace Expansa\Builders\Tree;

/**
 * Item of a nested tree from Tree::get(): its place in the tree as properties, the keys it was
 * added with read as properties too (`$item->title`, `$item->url`); a missing key is null.
 *
 * @property-read string    $id
 * @property-read string    $parent_id
 * @property-read int|float $position
 * @package Expansa\Builders
 */
final class Item
{
    public function __construct(

        /**
         * Keys the item was added with.
         */
        public readonly array $data,

        /**
         * Depth in the tree, 0 for the top level.
         */
        public readonly int $depth = 0,

        /**
         * Visible children, sorted by `position`; empty for a leaf.
         *
         * @var Item[]
         */
        public readonly array $children = [],
    ) {}

    /**
     * Get a key the item was added with, null if it has none.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Whether the item was added with the key and it is not null.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
}
