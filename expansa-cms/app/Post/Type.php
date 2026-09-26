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
         * Whether items can be exported.
         */
        public bool $canExport = true,

        /**
         * Whether items can be imported.
         */
        public bool $canImport = true,

        /**
         * User capabilities required to see the type's admin menu items.
         *
         * @var string[]
         */
        public array $capabilities = ['typesEdit'],

        /**
         * Admin menu icon: a Phosphor class name or a base64-encoded SVG.
         */
        public string $menuIcon = 'ph ph-folders',

        /**
         * Position of the item in the admin menu.
         */
        public int $menuPosition = 10,
    )
    {
        $postType = Safe::trim($key);

        if (!preg_match('/^[a-z_-]+$/', $postType)) {
            throw new InvalidArgumentException(
                t('Post type key "%s" must contain only lowercase letters, dashes, and underscores.', $postType)
            );
        }

        if (empty($postType) || strlen($postType) > 20) {
            throw new InvalidArgumentException(t('Post type key must be between 1 and 20 characters long.'));
        }

        $this->labelName       ??= t('Page');
        $this->labelNamePlural ??= t('Pages');
        $this->labelAllItems   ??= t('All Pages');
        $this->labelAdd        ??= t('Add Page');
        $this->labelEdit       ??= t('Edit Page');
        $this->labelUpdate     ??= t('Update Page');
        $this->labelView       ??= t('View Page');
        $this->labelSearch     ??= t('Search Page');
        $this->labelSave       ??= t('Save');
        $this->table             = Safe::tablename($key);

        /**
         * Show in dashboard menu.
         */
        if ($this->showInMenu) {
            Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems(
                [
                    [
                        'id'           => $postType,
                        'url'          => "edit?table=$postType",
                        'title'        => $this->labelNamePlural,
                        'capabilities' => $this->capabilities,
                        'icon'         => $this->menuIcon,
                        'position'     => $this->menuPosition,
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
        $type = Safe::snakecase($key);
        if (!Db::hasTable(EX_DB['prefix'] . $type)) {
            Hook::call('createPostsTable', $type);
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
     * Retrieves data (objects) of registered record types.
     * Not the records themselves, but the record type registration data.
     * You can filter the output by a variety of criteria.
     *
     * @param array $args      Array of criteria by which posts types will be selected.
     *                         For the value of each parameter, see the description of the "Type::register" method.
     * @param string $operator Optional. The logical operation to perform. 'or' means only one
     *                         element from the array needs to match; 'and' means all elements
     *                         must match; 'not' means no elements may match. Default 'and'.
     *
     * @return array
     */
    public static function fetch(array $args = [], string $operator = 'and'): array
    {
        return Arr::filter(self::$items ?? [], $args, $operator);
    }

    /**
     * Get registered types for using in select field.
     */
    public static function options(array $filters = []): array
    {
        $types = Arr::filter(self::$items ?? [], $filters, 'and');
        foreach ($types as $type) {
            $types[ $type->key ] = sprintf('%s (%s)', $type->labelName, $type->key);
        }
        return $types;
    }
}
