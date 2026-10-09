<?php

declare(strict_types=1);

namespace Expansa\Builders;

use Closure;
use InvalidArgumentException;

/**
 * Named tree of items: menu, comments, taxonomies. Items are added flat with `id` and `parent_id`,
 * render() nests them by parents and sorts by `position`:
 *
 * ```php
 * Tree::attach('main-menu', fn (Tree $tree) => $tree->addItems([
 *     ['id' => 'posts', 'title' => 'Posts', 'url' => 'posts', 'position' => 10],
 *     ['id' => 'tags', 'title' => 'Tags', 'url' => 'tags', 'parent_id' => 'posts'],
 * ]));
 *
 * echo Tree::render('main-menu', function (array $items, Tree $tree) {
 *     foreach ($items as $item) {
 *         echo $tree->format('<a href="%url$s">%title$s</a>', $item);
 *     }
 * });
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
     * Set how the capabilities of items are checked; items that fail are left out of render().
     *
     * @param Closure|null $allows `fn (string[] $capabilities): bool`, e.g. through Access.
     * @return void
     */
    public static function configure(?Closure $allows = null): void
    {
        self::$allows = $allows;
    }

    /**
     * Get a tree, an empty one if nothing is attached to it yet.
     *
     * @param string $name
     * @return Tree
     */
    public static function get(string $name): Tree
    {
        return self::$trees[$name] ??= new Tree($name);
    }

    /**
     * Change a tree, usually add its items: `fn (Tree $tree) => $tree->addItems([...])`.
     *
     * @param string                $name
     * @param callable(Tree): mixed $function
     * @return void
     */
    public static function attach(string $name, callable $function): void
    {
        $function(self::get($name));
    }

    /**
     * Render a tree: the function gets the nested items and the tree and prints the markup.
     * Items are nested under `children`, have `depth` from 0 and are sorted by `position`.
     *
     * @param string                       $name
     * @param callable(array, Tree): mixed $function
     * @return string The printed markup.
     */
    public static function render(string $name, callable $function): string
    {
        $tree = self::get($name);

        ob_start();
        $function($tree->nest(), $tree);

        return (string) ob_get_clean();
    }

    /**
     * Render nested arrays, e.g. directories: the callback prints one level for an item,
     * `@nested` in its output is replaced with the rendered children of the item.
     *
     * @param array                               $items
     * @param callable(int, int|string, mixed): mixed $callback Gets the depth from 1, the key and the item.
     * @param int                                 $depth    Depth of the parent level.
     * @return string
     */
    public static function build(array $items, callable $callback, int $depth = 0): string
    {
        $html = '';

        foreach ($items as $key => $item) {
            ob_start();
            $callback($depth + 1, $key, $item);
            $html .= ob_get_clean();

            if (is_array($item)) {
                $html = str_replace('@nested', self::build($item, $callback, $depth + 1), $html);
                // the replaced marker leaves blank lines
                $html = preg_replace("/^\s*[\r\n]*\s*$/m", '', $html);
            }
        }

        return $html;
    }

    /**
     * Whether the items of every tree leading to a page allow it: a page of a menu item is closed
     * to whoever doesn't see the item. A URL of no item is allowed.
     *
     * @param string               $path  Page path, e.g. `settings`.
     * @param array<string, mixed> $query Query of the request: an item URL with a query matches when its values do.
     * @return bool
     */
    public static function allowsUrl(string $path, array $query = []): bool
    {
        foreach (self::$trees as $tree) {
            foreach ($tree->items as $item) {
                $url = parse_url((string) ($item['url'] ?? ''));

                parse_str($url['query'] ?? '', $itemQuery);

                $isPage = trim($url['path'] ?? '', '/') === trim($path, '/')
                    && array_intersect_assoc($itemQuery, $query) === $itemQuery;
                if ($isPage && ! self::allows($item)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Add an item: `id` is required, `parent_id` nests it, `position` orders it among its siblings,
     * `capabilities` hide it from whoever lacks one of them; other keys are free.
     *
     * @param array<string, mixed> $item
     * @return void
     * @throws InvalidArgumentException If the item has no `id`.
     */
    public function addItem(array $item): void
    {
        if (trim((string) ($item['id'] ?? '')) === '') {
            throw new InvalidArgumentException(sprintf('An item of the "%s" tree has no ID.', $this->name));
        }

        $this->items[] = [
            'position'  => 0,
            'parent_id' => '',
            ...$item,
        ];
    }

    /**
     * Add several items, see addItem().
     *
     * @param array<int, array<string, mixed>> $items
     * @return void
     */
    public function addItems(array $items): void
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * Put item values into a template by name: `%title$s`, `%count$d`, any vsprintf() format.
     *
     * @param string               $template
     * @param array<string, mixed> $item
     * @return string
     */
    public function format(string $template, array $item): string
    {
        $positions = array_flip(array_keys($item));

        $template = preg_replace_callback(
            '/(^|[^%])%([a-zA-Z0-9_-]+)\$/',
            fn (array $match) => $match[1] . '%' . (($positions[$match[2]] ?? 0) + 1) . '$',
            $template
        );

        return vsprintf($template, array_values($item));
    }

    /**
     * Get the allowed items of a parent with their children, sorted by `position`;
     * items of the same position keep the order they were added in.
     *
     * @param string $parentId
     * @param int    $depth
     * @return array<int, array<string, mixed>>
     */
    private function nest(string $parentId = '', int $depth = 0): array
    {
        $items = [];

        foreach ($this->items as $item) {
            if ($item['parent_id'] !== $parentId || ! self::allows($item)) {
                continue;
            }

            $item['depth'] = $depth;

            $children = $this->nest(trim((string) $item['id']), $depth + 1);
            if ($children !== []) {
                $item['children'] = $children;
            }

            $items[] = $item;
        }

        usort($items, fn (array $a, array $b) => $a['position'] <=> $b['position']);

        return $items;
    }

    /**
     * Whether an item passes the capability check of configure().
     *
     * @param array<string, mixed> $item
     * @return bool
     */
    private static function allows(array $item): bool
    {
        $capabilities = (array) ($item['capabilities'] ?? []);

        return self::$allows === null || $capabilities === [] || (bool) (self::$allows)($capabilities);
    }
}
