<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Support\Arr;

/**
 * A class for displaying various tree-like structures.
 * Use it for output a tree of menu items, comments, taxonomies & many more.
 */
final class Tree
{
    use \Expansa\Patterns\Multiton;

    /**
     * Tree structures list.
     */
    public array $list;

    /**
     * Tree structure name.
     */
    public string $name;

    /**
     * TODO: The maximum number of items to crawl.
     *
     * $depth = -1 means flatly display every element (including child elements).
     * $depth = 0  means display all levels.
     * $depth > 0  specifies the number of display levels.
     */
    public int $depth = 99;

    /**
     * Register new tree structure.
     *
     * @param string $name
     * @param callable|null $function
     */
    public static function attach(string $name, callable $function = null): void
    {
        $tree = self::init($name);
        $name = Safe::html($name);

        if (empty($tree->list[$name])) {
            $tree->list[$name] = [];
        }

        $tree->name = $name;
        if (is_callable($function)) {
            $function($tree);
        }
    }

    /**
     * Output data of tree structure.
     *
     * @param string $name
     * @param callable $function
     */
    public static function view(string $name, callable $function): void
    {
        $tree  = self::init($name);
        $items = $tree->parse($tree->list[$name] ?? []);

        if (is_callable($function)) {
            $function($items, $tree);
        }
    }

    /**
     * Return data of tree structure.
     *
     * @param string $name
     * @param callable $function
     * @return string
     */
    public static function include(string $name, callable $function): string
    {
        ob_start();
        self::view($name, $function);
        return ob_get_clean();
    }

    public static function build(array $items, callable $callback, int $depth = 0): string
    {
        $content = '';
        foreach ($items as $key => $item) {
            ob_start();
            $callback($depth + 1, $key, $item);
            $content .= ob_get_clean();

            if (is_array($item)) {
                $content = str_replace('@nested', self::build($item, $callback, $depth + 1), $content);
                // remove empty string
                $content = preg_replace("/^\s*[\r\n]*\s*$/m", '', $content);
            }
        }
        return $content;
    }

    /**
     * Add a top-level menu page.
     * This function takes a capability that is used to determine whether
     * a page is included in the menu or not.
     *
     * The function which is hooked in to handle the output of the page must check
     * that the user has the required capability as well.
     */
    public function addItem(array $item): void
    {
        $item_id = trim((string) ( $item['id'] ?? '' ));
        if (! $item_id) {
            new Error('tree-add-item', I18n::_t('Tree item ID is required.'));
        }

        $item = array_replace(
            [
                'id'        => '',
                'position'  => 0,
                'parent_id' => '',
            ],
            $item
        );

        $this->list[$this->name][] = $item;
    }

    /**
     * Bulk add tree items.
     *
     * @param array $items
     */
    public function addItems(array $items): void
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    /**
     * The method takes over the routine work of forming dependencies and sorting tree elements.
     *
     * Parses a one-dimensional array with elements and forms a multidimensional one, taking into account nesting.
     * Sorts array elements in ascending order of the value of the `position` field.
     *
     * @param array $elements List of tree
     * @param string|null $parent_id Parent ID
     * @param int $depth depth of parsing
     *
     * @return array
     */
    public function parse(array $elements, ?string $parent_id = '', int $depth = 0): array
    {
        $tree = [];

        foreach ($elements as $element) {
            $element_id = trim($element['id'] ?? '');
            if ($element['parent_id'] === $parent_id) {
                $element['depth'] = $depth;

                $children = $this->parse($elements, $element_id, $depth + 1);

                if ($children) {
                    $element['children'] = $children;
                }

                $tree[] = $element;
            }
        }

        // TODO: неправильно сортирует, если у всех элементов значение position одинаковое
        return Arr::sort($tree, 'position');
    }

    /**
     * Like native vsprintf, but accepts $args keys instead of order index.
     * Both numeric and strings matching /[a-zA-Z0-9_-]+/ are allowed.
     *
     * Example: vsprintf( 'y = %y$d, x = %x$1.1f', [ 'x' => 1, 'y' => 2 ] )
     * Result:  'y = 2, x = 1.0'
     *
     * $args also can be object, then it's properties are retrieved using get_object_vars().
     * '%s' without argument name works fine too. Everything vsprintf() can do is supported.
     *
     * @param string       $str
     * @param array|object $args
     * @return string
     */
    public function vsprintf(string $str, array|object $args): string
    {
        if (is_object($args)) {
            $args = get_object_vars($args);
        } elseif (! is_array($args)) {
            return '';
        }

        $map = array_flip(array_keys($args));

        $new_str = preg_replace_callback(
            '/(^|[^%])%([a-zA-Z0-9_-]+)\$/',
            function ($m) use ($map) {
                return $m[1] . '%' . ( ( $map[$m[2]] ?? 0 ) + 1 ) . '$';
            },
            $str
        );

        return vsprintf($new_str, $args);
    }
}
