<?php

declare(strict_types=1);

namespace App\Post;

use App\Models\User;
use Expansa\Builders\Tree;
use Expansa\Facades\Access;
use Expansa\Support\Arr;
use InvalidArgumentException;

/**
 * Taxonomy: a registered way to group the posts of some types by terms, like categories or tags.
 * The terms of every taxonomy live in the `terms` table under its key, see Models\Term; a post gets
 * them with Models\Post::setTerms(). The list of the terms is a submenu item of each type of the taxonomy.
 *
 * ```php
 * Taxonomy::register(
 *     key: 'genres',
 *     labelName: t('Genre'),
 *     labelNamePlural: t('Genres'),
 *     labelAdd: t('Add New Genre'),
 *     labelEdit: t('Edit Genre'),
 *     labelSearch: t('Search Genres'),
 *     types: ['books'],
 *     hierarchical: true,
 * );
 * ```
 *
 * @package App\Post
 */
final class Taxonomy
{
    /**
     * Registered taxonomies by key.
     *
     * @var array<string, Taxonomy>
     */
    private static array $items = [];

    private function __construct(

        /**
         * Unique key, 1-32 lowercase letters, dashes or underscores; also the `%taxonomy%` of the term URLs
         * and the query parameter of the filter of the posts table.
         */
        public string $key {
            set {
                if (! preg_match('/^[a-z_-]{1,32}$/', $value)) {
                    throw new InvalidArgumentException(
                        sprintf('Taxonomy key "%s" must be 1-32 lowercase letters, dashes or underscores.', $value)
                    );
                }

                $this->key = $value;
            }
        },

        /**
         * Singular term name.
         */
        public string $labelName,

        /**
         * Plural term name, the title of the list of the terms and of their column in the posts table.
         */
        public string $labelNamePlural,

        /**
         * Text for "Add New Item": the title and the button of the form of a new term.
         */
        public string $labelAdd,

        /**
         * Text for "Edit Item", the title of the editor of a term.
         */
        public string $labelEdit,

        /**
         * Text for "Search Items".
         */
        public string $labelSearch,

        /**
         * Keys of the post types the terms are for.
         *
         * @var string[]
         */
        public array $types = [],

        /**
         * Whether the terms have pages on the site, see Models\Term::url().
         */
        public bool $public = true,

        /**
         * Whether terms can have parents, like categories; terms without them are like tags.
         */
        public bool $hierarchical = false,

        /**
         * Whether to add the list of the terms to the admin menu of each type.
         */
        public bool $showInMenu = true,

        /**
         * Whether the post editor has a field to choose the terms of the post.
         */
        public bool $showInEditor = true,

        /**
         * Whether the posts table has a column and a filter of the terms.
         */
        public bool $showInTable = true,

        /**
         * Whether the terms can be added as items of the navigation menus.
         */
        public bool $showInNavMenus = true,

        /**
         * User capabilities required to see the list of the terms and to change them.
         *
         * @var string[]
         */
        public array $capabilities = ['manage_terms'],

        /**
         * Position of the list among the submenu items of a type.
         */
        public int $menuPosition = 20,
    ) {
        if ($this->showInMenu) {
            Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->append(array_map(fn (string $type) => [
                'id'           => "taxonomy-$this->key-$type",
                'url'          => $this->url($type),
                'title'        => $this->labelNamePlural,
                'capabilities' => $this->capabilities,
                'parent_id'    => $type,
                'position'     => $this->menuPosition,
            ], $this->types)));
        }
    }

    /**
     * Register a taxonomy; a key registered before keeps its taxonomy.
     *
     * @param mixed ...$args Named arguments of the constructor.
     * @return Taxonomy
     * @throws InvalidArgumentException When the key is not valid.
     */
    public static function register(mixed ...$args): self
    {
        $taxonomy = new self(...$args);

        return self::$items[$taxonomy->key] ??= $taxonomy;
    }

    /**
     * Unregister a taxonomy, its terms stay in the database.
     *
     * @param string $key
     * @return void
     */
    public static function unregister(string $key): void
    {
        unset(self::$items[$key]);
    }

    /**
     * Get a registered taxonomy.
     *
     * @param string $key
     * @return Taxonomy|null
     */
    public static function get(string $key): ?self
    {
        return self::$items[$key] ?? null;
    }

    /**
     * Get the registered taxonomies whose properties have the given values, see Arr::filter().
     *
     * @param array<string, mixed> $args     Values by property, e.g. `['hierarchical' => true]`.
     * @param string               $operator `and`: every value matches, `or`: one of them, `not`: none.
     * @return array<string, Taxonomy>
     */
    public static function filter(array $args = [], string $operator = 'and'): array
    {
        return Arr::filter(self::$items, $args, $operator);
    }

    /**
     * Get the taxonomies of a post type.
     *
     * @param string $type Key of the post type.
     * @return array<string, Taxonomy>
     */
    public static function forType(string $type): array
    {
        return array_filter(self::$items, fn (Taxonomy $taxonomy) => in_array($type, $taxonomy->types, true));
    }

    /**
     * Whether a user may change the terms: has every capability of the taxonomy.
     *
     * @param User|null $user
     * @return bool False for a guest.
     */
    public function canManage(?User $user): bool
    {
        return $user !== null && array_all($this->capabilities, fn (string $capability) => Access::allows($user, $capability));
    }

    /**
     * Get the dashboard URL of the list of the terms, relative to the dashboard.
     *
     * @param string $type Key of the post type whose menu the list is under; its posts are counted by the links.
     * @return string
     */
    public function url(string $type = ''): string
    {
        return "edit?table=terms&taxonomy=$this->key" . ($type !== '' ? "&type=$type" : '');
    }
}
