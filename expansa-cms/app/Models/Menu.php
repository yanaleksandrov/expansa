<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Values\Field;
use App\Post\Type;
use App\Support\Permalinks;
use Closure;
use Expansa\Builders\Tree;
use Expansa\Database\Attribute;
use Expansa\Database\Model;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Database\Traits\HasTimestamps;
use Expansa\Database\Traits\HasValidation;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;
use Expansa\Support\Error;

/**
 * Navigation menu of the site, edited on the `nav-editor` page: a title, the theme locations it is shown
 * in and its items. A location belongs to one menu at a time.
 *
 * The items are stored as the items of Expansa\Builders\Tree, flat in the order of the editor:
 * `['id' => ..., 'parent_id' => ..., 'position' => ..., 'type' => 'link'|'post', 'object' => post type,
 * 'objectId' => post ID]` and the values of the fields of itemFields() by their keys: the title, the URL,
 * the target, the relations and so on, with the fields plugins add by the `menuItemFields` filter.
 * A link has its own URL, a post item gets the URL and, with an empty title, the title of its post when shown.
 *
 * register() attaches the menu of each location as the `menu-<location>` tree. The active theme declares
 * its locations with Theme::setMenus(), bootstrap.php passes them to setThemeLocations(); a plugin adds its own
 * by the `menuLocations` filter. A theme outputs a menu like the dashboard outputs its own, `active` marks
 * the current page:
 *
 * ```php
 * $this->setMenus(['primary' => t('Header'), 'footer' => t('Footer')]);
 * Hook::add('menuLocations', fn (array $locations) => [...$locations, 'sidebar' => t('Sidebar')]);
 *
 * tree('menu-primary', function (array $items) { ... $item->title, $item->url, $item->rel, $item->active ... });
 * ```
 *
 * A location the theme no longer declares stays with its menu until it is unchecked: switching back
 * to the theme shows the menu there again.
 *
 * @property int    $id
 * @property string $title
 * @property array  $locations
 * @property array  $items
 * @property string $createdAt
 * @property string $updatedAt
 */
class Menu extends Model
{
    use HasSanitizing;
    use HasTimestamps;
    use HasValidation;

    /**
     * Deepest level of an item, 0 for the top one; a deeper item is moved up to it.
     */
    public const int MAX_DEPTH = 5;

    /**
     * Capabilities that show an item by its `visibility`, checked by Tree::configure() in bootstrap.php.
     */
    public const array VISIBILITY = [
        'all'    => [],
        'users'  => ['read'],
        'admins' => ['manage_options'],
    ];

    public protected(set) string $table = 'menus';

    /**
     * Locations of the active theme, null without a theme; see setThemeLocations().
     */
    private static ?Closure $declared = null;

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    public protected(set) array $fillable = [
        'title',
    ];

    protected function getSanitizerRules(): array
    {
        return [
            'title' => 'trim',
        ];
    }

    protected function validatorRules(): array
    {
        return [
            'title' => 'required',
        ];
    }

    protected function validatorExtend(): void
    {
    }

    /**
     * Keys of the theme locations the menu is shown in.
     */
    protected function locations(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Json::decode($value, true) : [],
            set: fn ($value) => Json::encode(array_values(array_unique(array_map(strval(...), (array) $value)))),
        );
    }

    /**
     * Items as the items of a Tree, normalized by normalize() when set.
     */
    protected function items(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? Json::decode($value, true) : [],
            set: fn ($value) => Json::encode(self::normalize((array) $value)),
        );
    }

    /**
     * Set where the locations of the active theme come from: the callback is called when they are read,
     * after the theme is loaded, and returns null without an active theme.
     *
     * @param Closure(): (array<string, string>|null) $locations Labels by key.
     * @return void
     */
    public static function setThemeLocations(Closure $locations): void
    {
        self::$declared = $locations;
    }

    /**
     * Get the locations a menu can be shown in, by key: those of the active theme, `primary` and `footer`
     * without a theme; plugins change them with the `menuLocations` filter.
     *
     * @return array<string, string> Labels by key.
     */
    public static function themeLocations(): array
    {
        $declared = self::$declared ? (self::$declared)() : null;

        return (array) Hook::call('menuLocations', $declared ?? [
            'primary' => t('Primary'),
            'footer'  => t('Footer'),
        ]);
    }

    /**
     * Get the fields of an item by key, in the order of the editor; themes and plugins add their own
     * with the `menuItemFields` filter: `fn (array $fields) => [...$fields, 'badge' => new Field(t('Badge'))]`.
     *
     * @return array<string, Field>
     */
    public static function itemFields(): array
    {
        $empty   = t('Empty');
        $classes = self::classes(...);

        return array_filter((array) Hook::call('menuItemFields', [
            'title'          => new Field(t('Label', context: 'menu'), icon: 'ph ph-text-aa', placeholder: $empty),
            'url'            => new Field(
                'URL',
                icon: 'ph ph-link',
                placeholder: 'https://',
                types: ['link'],
                sanitizer: fn (mixed $value) => is_scalar($value) ? Safe::href(trim((string) $value)) : '',
            ),
            'titleAttribute' => new Field(t('Title Attribute'), icon: 'ph ph-anchor-simple', placeholder: $empty),
            'description'    => new Field(t('Description'), icon: 'ph ph-text-a-underline', placeholder: $empty),
            'icon'           => new Field(t('Icon'), icon: 'ph ph-shapes', placeholder: 'ph ph-house', sanitizer: $classes),
            'classes'        => new Field(t('CSS Classes'), icon: 'ph ph-code', placeholder: $empty, sanitizer: $classes),
            'target'         => new Field(t('Open In'), 'select', 'ph ph-crosshair-simple', [
                ''       => t('Same Tab'),
                '_blank' => t('New Tab'),
            ]),
            'visibility'     => new Field(t('Visibility'), 'select', 'ph ph-eye', [
                'all'    => t('Everyone'),
                'users'  => t('Logged-In Users'),
                'admins' => t('Administrators'),
            ], 'all'),
            // `noopener noreferrer` are added to a link opened in a new tab, see links()
            'rel'            => new Field(t('Link Relations'), 'multiselect', 'ph ph-link-simple', [
                'nofollow'  => 'nofollow',
                'sponsored' => 'sponsored',
                'ugc'       => 'ugc',
            ], [], $empty),
        ]), fn (mixed $field) => $field instanceof Field);
    }

    /**
     * Attach the menu of each theme location as the `menu-<location>` tree, for the site: the URLs and
     * titles of the post items are read in a query per post type.
     *
     * @return void
     */
    public static function register(): void
    {
        $locations = array_keys(self::themeLocations());
        foreach (self::all() ?: [] as $menu) {
            $shown = array_intersect($menu->locations, $locations);
            if ($shown === []) {
                continue;
            }

            $links = $menu->links();
            foreach ($shown as $location) {
                Tree::attach('menu-' . $location, fn (Tree $tree) => $tree->append($links));
            }
        }
    }

    /**
     * Get the IDs of the posts the items link to, by post type.
     *
     * @return array<string, int[]>
     */
    public function posts(): array
    {
        $ids = [];
        foreach ($this->items as $item) {
            if ($item['type'] === 'post') {
                $ids[$item['object']][] = $item['objectId'];
            }
        }

        return $ids;
    }

    /**
     * Find a menu by ID.
     *
     * @param int $id
     * @return Menu|Error
     */
    public static function find(int $id): Menu|Error
    {
        $menu = $id > 0 ? parent::get($id) : null;

        return $menu instanceof Menu ? $menu : error('menu-find', t('Menu not found'));
    }

    /**
     * Create a menu.
     *
     * @param array{title?: string, locations?: string[], items?: array} $data
     * @return Menu|Error
     */
    public static function create(array $data): Menu|Error
    {
        $menu            = new self($data);
        $menu->locations = $data['locations'] ?? [];
        $menu->items     = $data['items'] ?? [];

        return $menu->store('menu-create');
    }

    /**
     * Update the menu with the given attributes.
     *
     * @param array{title?: string, locations?: string[], items?: array} $data
     * @return Menu|Error
     */
    public function update(array $data): Menu|Error
    {
        $this->fill($data);

        if (array_key_exists('locations', $data)) {
            $this->locations = $data['locations'];
        }

        if (array_key_exists('items', $data)) {
            $this->items = $data['items'];
        }

        return $this->store('menu-update');
    }

    /**
     * Validate and save the menu, then take its locations from the other menus.
     *
     * @param string $code Code of the error.
     * @return Menu|Error
     */
    private function store(string $code): Menu|Error
    {
        if (! $this->isValid()) {
            return error($code, $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error($code, t('Failed to save the menu to the database.'));
        }

        foreach (self::all() ?: [] as $menu) {
            $locations = array_diff($menu->locations, $this->locations);
            if ($menu->id !== $this->id && count($locations) !== count($menu->locations)) {
                $menu->locations = $locations;
                $menu->save();
            }
        }

        return $this;
    }

    /**
     * Get the items to show: a post item gets the URL and the title of its post, `rel` becomes a string,
     * `visibility` the capabilities of Tree; an item of a missing or unpublished post is left out, its
     * subitems take its place.
     *
     * @return array<int, array<string, mixed>>
     */
    private function links(): array
    {
        $posts = [];
        foreach ($this->posts() as $key => $ids) {
            $type = Type::get($key);
            if (! $type instanceof Type || ! $type->public) {
                continue;
            }

            $paths = Post::paths($key, $ids);
            foreach (Db::select($type->table, ['id [Int]', 'title'], ['id' => $ids, 'status' => 'publish']) ?: [] as $row) {
                if (isset($paths[$row['id']])) {
                    $posts[$key][$row['id']] = [
                        'title' => (string) $row['title'],
                        'url'   => url(Permalinks::post($paths[$row['id']], $row['id'], $key)),
                    ];
                }
            }
        }

        $links   = [];
        $parents = [];
        foreach ($this->items as $item) {
            $parent = $parents[$item['parent_id']] ?? $item['parent_id'];
            $post   = $item['type'] === 'post' ? $posts[$item['object']][$item['objectId']] ?? null : null;
            if ($item['type'] === 'post' && $post === null) {
                $parents[$item['id']] = $parent;
                continue;
            }

            $rel = (array) ($item['rel'] ?? []);
            if (($item['target'] ?? '') === '_blank') {
                $rel = [...$rel, 'noopener', 'noreferrer'];
            }

            $links[] = [
                ...$item,
                'parent_id'    => $parent,
                'title'        => $item['title'] !== '' ? $item['title'] : (string) ($post['title'] ?? ''),
                'url'          => $post['url'] ?? $item['url'],
                'rel'          => implode(' ', $rel),
                'capabilities' => self::VISIBILITY[$item['visibility'] ?? 'all'] ?? [],
            ];
        }

        return $links;
    }

    /**
     * Normalize the items: the fields of itemFields() sanitize their values, a field not for the type of
     * the item gets its default, unknown keys are dropped; a parent comes before its subitems and is less
     * than MAX_DEPTH levels deep; a link without a safe URL and a post item without post are left out,
     * their subitems take their place. The position of an item is its place in the list.
     *
     * @param array $items
     * @return array<int, array<string, mixed>>
     */
    private static function normalize(array $items): array
    {
        $fields  = self::itemFields();
        $list    = [];
        $parents = [];
        $depths  = [];
        foreach ($items as $item) {
            $id = is_array($item) ? trim((string) ($item['id'] ?? '')) : '';
            if ($id === '' || isset($parents[$id])) {
                continue;
            }

            // a left out parent is replaced by its own parent, an unknown or later one by the top level
            $parent = (string) ($item['parent_id'] ?? '');
            while ($parent !== '' && ! isset($depths[$parent])) {
                $parent = $parents[$parent] ?? '';
            }
            while ($parent !== '' && $depths[$parent] >= self::MAX_DEPTH) {
                $parent = $parents[$parent];
            }
            $parents[$id] = $parent;

            $type = ($item['type'] ?? '') === 'post' ? 'post' : 'link';
            $node = [
                'id'        => $id,
                'parent_id' => $parent,
                'position'  => count($list),
                'type'      => $type,
                'object'    => $type === 'post' ? (string) ($item['object'] ?? '') : '',
                'objectId'  => $type === 'post' ? max(0, (int) ($item['objectId'] ?? 0)) : 0,
            ];
            foreach ($fields as $key => $field) {
                $isFor      = $field->types === [] || in_array($type, $field->types, true);
                $node[$key] = $isFor ? $field->sanitize($item[$key] ?? null) : $field->default;
            }

            if ($type === 'post' ? $node['object'] === '' || $node['objectId'] === 0 : ($node['url'] ?? '') === '') {
                continue;
            }

            $depths[$id] = $parent === '' ? 0 : $depths[$parent] + 1;
            $list[]      = $node;
        }

        return $list;
    }

    /**
     * Keep only the characters of CSS classes separated by spaces.
     *
     * @param mixed $value
     * @return string
     */
    private static function classes(mixed $value): string
    {
        return is_scalar($value) ? trim(preg_replace(['/[^\w\s-]+/', '/\s+/'], ['', ' '], (string) $value)) : '';
    }
}
