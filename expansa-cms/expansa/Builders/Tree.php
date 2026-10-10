<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Closure;
use Expansa\Builders\Tree\Item;
use InvalidArgumentException;
use LogicException;

/**
 * Named tree of items: menu, comments, taxonomies. Items are added flat with `id` and `parent_id`,
 * get() gives them nested under `children` and sorted by `position`, a template loops over them:
 *
 * ```php
 * Tree::attach('main-menu', fn (Tree $tree) => $tree->append([
 *     ['id' => 'posts', 'title' => 'Posts', 'url' => 'posts', 'position' => 10],
 *     ['id' => 'tags', 'title' => 'Tags', 'url' => 'tags', 'parent_id' => 'posts'],
 * ]));
 *
 * foreach (Tree::get('main-menu') as $item) {
 *     echo $item->title, $item->depth, count($item->children);
 * }
 * ```
 *
 * @package Expansa\Builders
 */
final class Tree
{
    /**
     * Trees by name.
     *
     * @var array<string, Tree>
     */
    private static array $trees = [];

    /**
     * Checks the `capabilities` of an item: `fn (string[] $capabilities): bool`; null shows every item.
     */
    private static ?Closure $allows = null;

    /**
     * Items as added, without nesting and sorting.
     *
     * @var array<int, array<string, mixed>>
     */
    public private(set) array $items = [];

    public function __construct(

        /**
         * Name of the tree, e.g. `dashboard-main-menu`.
         */
        public readonly string $name,
    ) {}

    /**
     * Set how the capabilities of items are checked; items that fail are left out of get().
     *
     * @param Closure|null $allows `fn (string[] $capabilities): bool`, e.g. through Access.
     * @return void
     */
    public static function configure(?Closure $allows = null): void
    {
        self::$allows = $allows;
    }

    /**
     * Get the items of a tree to output: the visible ones with their visible `children` (empty for a leaf)
     * and `depth` from 0, sorted by `position`; items of the same position keep the order they were added in.
     *
     * @param string $name
     * @return Item[] Empty for a tree nothing is attached to.
     */
    public static function get(string $name): array
    {
        return isset(self::$trees[$name]) ? self::$trees[$name]->nested() : [];
    }

    /**
     * Change a tree, usually add its items: `fn (Tree $tree) => $tree->append([...])`.
     * The tree is created on the first attach.
     *
     * @param string                $name
     * @param callable(Tree): mixed $function
     * @return void
     */
    public static function attach(string $name, callable $function): void
    {
        $function(self::$trees[$name] ??= new Tree($name));
    }

    /**
     * Output a level of a tree: the callback gets its items and their depth, wraps and loops over them.
     * Inside it, walk() without a callback reuses the callback it runs in: `Tree::walk($item->children)`.
     * An empty list or not a list outputs nothing, so neither the template checks it.
     *
     * @param mixed                             $items    Name of a tree, or items from get().
     * @param callable(Item[], int): mixed|null $callback Null inside another walk() to reuse its callback
     *                                                    for the next level.
     * @return void
     * @throws LogicException If there is no callback to reuse.
     */
    public static function walk(mixed $items, ?callable $callback = null): void
    {
        // levels being output, innermost last: the callback and the depth
        static $levels = [];

        if (is_string($items)) {
            $items = self::get($items);
        }
        if (! is_array($items) || $items === []) {
            return;
        }

        if ($callback !== null) {
            $depth = 0;
        } else {
            [$callback, $depth] = end($levels)
                ?: throw new LogicException('Tree::walk() needs a callback outside another walk().');
            $depth++;
        }

        $levels[] = [$callback, $depth];
        try {
            $callback($items, $depth);
        } finally {
            array_pop($levels);
        }
    }

    /**
     * Whether the page can be opened: a page of a menu item is closed to whoever doesn't see
     * the item in every tree that leads to it. A URL of no item can be opened.
     *
     * @param string               $path  Page path, e.g. `settings`.
     * @param array<string, mixed> $query Query of the request: an item URL with a query matches when its values do.
     * @return bool
     */
    public static function canOpen(string $path, array $query = []): bool
    {
        foreach (self::$trees as $tree) {
            foreach ($tree->items as $item) {
                $url = parse_url((string) ($item['url'] ?? ''));

                parse_str($url['query'] ?? '', $itemQuery);

                $isPage = trim($url['path'] ?? '', '/') === trim($path, '/')
                    && array_intersect_assoc($itemQuery, $query) === $itemQuery;
                if ($isPage && ! self::isVisible($item)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Add items: `id` is required, `parent_id` nests an item, `position` orders it among its siblings,
     * `capabilities` hide it from whoever lacks one of them; other keys are free.
     *
     * @param array<int, array<string, mixed>> $items
     * @return static
     * @throws InvalidArgumentException If an item has no `id`.
     */
    public function append(array $items): static
    {
        foreach ($items as $item) {
            if (trim((string) ($item['id'] ?? '')) === '') {
                throw new InvalidArgumentException(sprintf('An item of the "%s" tree has no ID.', $this->name));
            }

            $this->items[] = [
                'position'  => 0,
                'parent_id' => '',
                ...$item,
            ];
        }

        return $this;
    }

    /**
     * Get the visible items nested by parents, see get().
     *
     * @return Item[]
     */
    private function nested(): array
    {
        // children by parent, so every level is taken at once instead of searching all items for it
        $children = [];
        foreach ($this->items as $item) {
            if (self::isVisible($item)) {
                $children[$item['parent_id']][] = $item;
            }
        }

        return self::level($children, '', 0);
    }

    /**
     * Get the items of a parent with their children, sorted by `position`.
     *
     * @param array<int|string, array<int, array<string, mixed>>> $children Visible items by parent.
     * @param string                                               $parentId
     * @param int                                                  $depth
     * @return Item[]
     */
    private static function level(array $children, string $parentId, int $depth): array
    {
        $level = $children[$parentId] ?? [];

        usort($level, fn (array $a, array $b) => $a['position'] <=> $b['position']);

        $items = [];
        foreach ($level as $item) {
            $items[] = new Item($item, $depth, self::level($children, trim((string) $item['id']), $depth + 1));
        }

        return $items;
    }

    /**
     * Whether an item passes the capability check of configure().
     *
     * @param array<string, mixed> $item
     * @return bool
     */
    private static function isVisible(array $item): bool
    {
        $capabilities = (array) ($item['capabilities'] ?? []);

        return self::$allows === null || $capabilities === [] || (bool) (self::$allows)($capabilities);
    }
}
