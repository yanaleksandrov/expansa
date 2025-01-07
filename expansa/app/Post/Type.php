<?php

declare(strict_types=1);

namespace app\Post;

use app\Field;
use app\Tree;
use Expansa\Db;
use Expansa\Error;
use Expansa\I18n;
use Expansa\Safe;
use Expansa\Support\Arr;

class Type
{
    private static array $items = [];

    /**
     * @param string $key             Unique key. Must not exceed 20 characters and may contain only
     *                                lowercase alphanumeric characters, dashes, and underscores.
     * @param string $labelName       The name of the item displayed in the menu. Usually singular.
     * @param string $labelNamePlural The plural name of the item displayed in the menu.
     * @param string $labelAllItems   Text to display for "All Items".
     * @param string $labelAdd        Text to display for "Add Item".
     * @param string $labelEdit       Text to display for "Edit Item".
     * @param string $labelUpdate     Text to display for "Update Item".
     * @param string $labelView       Text to display for "View Item".
     * @param string $labelSearch     Text to display for "Search".
     * @param string $labelSave       Text to display for "Save".
     * @param string $table           The name of the database table where items are stored.
     * @param bool   $public          Whether the item is intended for public use via the admin interface or by front-end users.
     * @param bool   $hierarchical    Whether the structure is hierarchical (e.g., like pages). Default false.
     * @param bool   $searchable      Whether items of this type are available in front-end search.
     * @param bool   $showInMenu      Whether to display this item in the admin menu. If true, it is displayed
     *                                as a top-level menu item. If false, it is hidden from the menu.
     * @param bool   $showInBar       Whether to make this item available in the admin bar.
     * @param bool   $canExport       Whether items of this type can be exported. Default true.
     * @param bool   $canImport       Whether items of this type can be imported.
     * @param array  $capabilities    Array of user capabilities for this item type. Controls permissions for
     *                                actions such as editing and deleting items.
     * @param string $menuIcon        URL or class name for the icon used in the admin menu. Can be a base64-encoded
     *                                SVG or a Phosphor class name.
     * @param int    $menuPosition    The position in the menu order where this item should appear.
     */
    private function __construct(
        public string $key,
        public string $labelName,
        public string $labelNamePlural,
        public string $labelAllItems,
        public string $labelAdd,
        public string $labelEdit,
        public string $labelUpdate,
        public string $labelView,
        public string $labelSearch,
        public string $labelSave,
        public string $table = '',
        public bool $public = true,
        public bool $hierarchical = false,
        public bool $searchable = true,
        public bool $showInMenu = true,
        public bool $showInBar = true,
        public bool $canExport = true,
        public bool $canImport = true,
        public array $capabilities = ['typesEdit'],
        public string $menuIcon = 'ph ph-folders',
        public int $menuPosition = 10,
    )
    {
        $postType = Safe::kebabcase($key);
        if (empty($postType) || strlen($postType) > 20) {
            Error::add('post-type-name-length', I18n::_t('Post type key is empty or exceeds 20 characters'));
        }

        $this->labelName       ??= I18n::_t('Page');
        $this->labelNamePlural ??= I18n::_t('Pages');
        $this->labelAllItems   ??= I18n::_t('All Pages');
        $this->labelAdd        ??= I18n::_t('Add Page');
        $this->labelEdit       ??= I18n::_t('Edit Page');
        $this->labelUpdate     ??= I18n::_t('Update Page');
        $this->labelView       ??= I18n::_t('View Page');
        $this->labelSearch     ??= I18n::_t('Search Page');
        $this->labelSave       ??= I18n::_t('Save');
        $this->table             = Safe::tablename($key);

        /**
         * Show in dashboard menu.
         */
        if ($this->showInMenu) {
            Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems(
                [
                    [
                        'id'           => $postType,
                        'url'          => $postType,
                        'title'        => $this->labelNamePlural,
                        'capabilities' => $this->capabilities,
                        'icon'         => $this->menuIcon,
                        'position'     => $this->menuPosition,
                    ],
                    [
                        'id'           => sprintf('type-%s', $postType),
                        'url'          => $postType,
                        'title'        => $this->labelAllItems,
                        'capabilities' => $this->capabilities,
                        'parent_id'    => $postType,
                    ],
                ]
            ));
        }

        /**
         * DataBase table schema.
         *
         * @var array $schema
         */
        $schema = Db::schema();
        $type   = Safe::snakecase($key);
        if (empty($schema[ $type ])) {
            Schema::migrate($type);

            Field\Schema::migrate(EX_DB_PREFIX . $type, 'post');
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
        if (empty($items[ $type->key ])) {
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
        if (isset(self::$items[ $key ])) {
            unset(self::$items[ $key ]);
        }
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
