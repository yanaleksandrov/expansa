<?php

declare(strict_types=1);

namespace App\Post;

use Expansa\Builders\Tree;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;
use InvalidArgumentException;

class Type
{
    private static array $items = [];

    private function __construct(

        /**
         * Unique key, 1-20 lowercase letters, dashes or underscores; also used as the menu id and table name.
         */
        public string $key,

        /**
         * Singular item name, also shown in type select options.
         */
        public string $labelName,

        /**
         * Plural item name, used as the top-level admin menu title.
         */
        public string $labelNamePlural,

        /**
         * Text for "All Items", used as the admin submenu title.
         */
        public string $labelAllItems,

        /**
         * Text for "Add Item".
         */
        public string $labelAdd,

        /**
         * Text for "Edit Item".
         */
        public string $labelEdit,

        /**
         * Text for "Update Item".
         */
        public string $labelUpdate,

        /**
         * Text for "View Item".
         */
        public string $labelView,

        /**
         * Text for "Search".
         */
        public string $labelSearch,

        /**
         * Text for "Save".
         */
        public string $labelSave,

        /**
         * Database table of the items; always overwritten with the name derived from $key.
         */
        public string $table = '',

        /**
         * Whether items are available to front-end users; only public items get URL slugs.
         */
        public bool $public = true,

        /**
         * Whether items can have parents, like pages.
         */
        public bool $hierarchical = false,

        /**
         * Whether items are available in front-end search.
         */
        public bool $searchable = true,

        /**
         * Whether to add a top-level admin menu item with an "All Items" submenu.
         */
        public bool $showInMenu = true,

        /**
         * Whether the type is available in the admin bar.
         */
        public bool $showInBar = true,

        /**
         * Whether posts of the type can be added as items of the navigation menus.
         */
        public bool $showInNavMenus = true,

        /**
         * Whether items can be exported.
         */
        public bool $canExport = true,

        /**
         * Whether items can be imported.
         */
        public bool $canImport = true,

        /**
         * Whether the admin menu item has a "+" button that creates a draft item.
         */
        public bool $canCreate = false,

        /**
         * Whether deleted items go to the trash first, see Models\Post::delete(); without it they are deleted at once.
         */
        public bool $canTrash = true,

        /**
         * User capabilities required to see the type's admin menu items.
         *
         * @var string[]
         */
        public array $capabilities = ['types_edit'],

        /**
         * Admin menu icon: a Phosphor class name or a base64-encoded SVG.
         */
        public string $menuIcon = 'ph ph-folders',

        /**
         * Position of the item in the admin menu.
         */
        public int $menuPosition = 10,
    ) {
        $postType = Safe::trim($key);

        if (!preg_match('/^[a-z_-]+$/', $postType)) {
            throw new InvalidArgumentException(
                t('Post type key "%s" must contain only lowercase letters, dashes, and underscores.', $postType)
            );
        }

        if (empty($postType) || strlen($postType) > 20) {
            throw new InvalidArgumentException(t('Post type key must be between 1 and 20 characters long.'));
        }

        $this->table = Safe::tablename($key);

        /**
         * Show in dashboard menu.
         */
        if ($this->showInMenu) {
            Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->append(
                [
                    [
                        'id'           => $postType,
                        'url'          => "edit?table=$postType",
                        'title'        => $this->labelNamePlural,
                        'capabilities' => $this->capabilities,
                        'icon'         => $this->menuIcon,
                        'position'     => $this->menuPosition,
                        'create'       => $this->canCreate ? $this->labelAdd : null,
                    ],
                    [
                        'id'           => sprintf('type-%s', $postType),
                        'url'          => "edit?table=$postType",
                        'title'        => $this->labelAllItems,
                        'capabilities' => $this->capabilities,
                        'parent_id'    => $postType,
                    ],
                ]
            ));
        }

        /**
         * Add DB table for post type if not exists.
         */
        $type  = Safe::snakecase($key);
        $table = EX_DB['prefix'] . $type;
        if (! Db::hasTable($table)) {
            Hook::run('createPostsTable', $type);
        }
    }

    /**
     * Registers a post type.
     *
     * @param mixed ...$args
     * @return Type
     */
    public static function register(...$args): self
    {
        $type = new self(...$args);
        if (empty(self::$items[ $type->key ])) {
            self::$items[ $type->key ] = $type;
        }
        return $type;
    }

    /**
     * Unregister post type.
     *
     * @param string $key
     */
    public static function unregister(string $key): void
    {
        unset(self::$items[ $key ]);
    }

    /**
     * Get registered type.
     */
    public static function get(string $key): ?self
    {
        return self::$items[ $key ] ?? null;
    }

    /**
     * Check if type registered.
     *
     * @param string $key
     * @return bool
     */
    public static function exist(string $key): bool
    {
        return isset(self::$items[ $key ]);
    }

    /**
     * Get the registered types whose properties have the given values, see Arr::filter().
     *
     * @param array<string, mixed> $args     Values by property, e.g. `['public' => true]`.
     * @param string               $operator `and`: every value matches, `or`: one of them, `not`: none.
     * @return array<string, Type>
     */
    public static function filter(array $args = [], string $operator = 'and'): array
    {
        return Arr::filter(self::$items, $args, $operator);
    }

    /**
     * Get registered types for using in select field.
     */
    public static function options(array $filters = []): array
    {
        $types = Arr::filter(self::$items, $filters, 'and');
        foreach ($types as $type) {
            $types[ $type->key ] = sprintf('%s (%s)', $type->labelName, $type->key);
        }
        return $types;
    }
}
