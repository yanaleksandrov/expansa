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
use Expansa\Facades\Auth;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;
use Expansa\Support\Error;
use Expansa\Translation\Languages;

/**
 * Navigation menu of the site, edited on the `nav-editor` page: a title, the theme locations it is shown
 * in and its items. A location belongs to one menu at a time.
 *
 * The items are stored as the items of Expansa\Builders\Tree, flat in the order of the editor:
 * `['id' => ..., 'parent_id' => ..., 'position' => ..., 'type' => one of TYPES, 'object' => post type,
 * 'objectId' => post ID]` and the values of the fields of itemFields() by their keys: the title, the URL,
 * the target, the relations and so on, with the fields plugins add by the `menuItemFields` filter.
 * The ID of an item stays the same while the menu is edited.
 * A link has its own URL, a post item gets the URL and, with an empty title, the title of its post when shown;
 * a heading has no URL, a separator neither title, `auth` signs in or out and `profile` opens the profile.
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
 * The links of the locations are cached, forgetLinks() drops them when a menu or a post changes;
 * the dates, the language and the visitor are applied on every request.
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
     * Types of the items, see the description of the class.
     */
    public const array TYPES = ['link', 'post', 'heading', 'separator', 'auth', 'profile'];

    /**
     * Capabilities that show an item by its `visibility`, checked by Tree::configure() in bootstrap.php;
     * `guests` is checked by shown(), a capability can not tell a guest.
     */
    public const array VISIBILITY = [
        'all'    => [],
        'guests' => [],
        'users'  => ['read'],
        'admins' => ['manage_options'],
    ];

    /**
     * Types of the items that link somewhere: the link fields are for them.
     */
    private const array LINKS = ['link', 'post', 'auth', 'profile'];

    public protected(set) string $table = 'menus';

    /**
     * Locations of the active theme, null without a theme; see setThemeLocations().
     */
    private static ?Closure $declared = null;

    /**
     * Locale of the requested page, null on a site with one language; see setLanguage().
     */
    private static ?Closure $language = null;

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
     * Set how the locale of the requested page is found: an item for another language is not shown.
     *
     * @param Closure(): (string|null) $language Null shows the items of every language.
     * @return void
     */
    public static function setLanguage(Closure $language): void
    {
        self::$language = $language;
    }

    /**
     * Get the languages of a multilingual site, empty for a site with one language.
     *
     * @return array<string, string> Names by locale.
     */
    public static function languages(): array
    {
        $names = array_column(Languages::all(), 'name', 'locale');
        $codes = (array) Option::get('multilingual.languages', []);

        $locales = array_keys($codes);

        return array_combine($locales, array_map(fn (string $locale) => (string) ($names[$locale] ?? $locale), $locales));
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

        $labeled = ['link', 'post', 'heading', 'profile'];
        $fields  = [
            'title'          => new Field(
                t('Label', context: 'menu'),
                icon: 'ph ph-text-aa',
                placeholder: $empty,
                types: $labeled,
            ),
            'url'            => new Field(
                'URL',
                icon: 'ph ph-link',
                placeholder: 'https://',
                types: ['link'],
                sanitizer: fn (mixed $value) => is_scalar($value) ? Safe::href(trim((string) $value)) : '',
            ),
            'titleAttribute' => new Field(
                t('Title Attribute'),
                icon: 'ph ph-anchor-simple',
                placeholder: $empty,
                types: self::LINKS,
            ),
            'description'    => new Field(t('Description'), icon: 'ph ph-text-a-underline', placeholder: $empty, types: $labeled),
            'icon'           => new Field(
                t('Icon'),
                icon: 'ph ph-shapes',
                placeholder: 'ph ph-house',
                types: [...$labeled, 'auth'],
                sanitizer: $classes,
            ),
            'classes'        => new Field(t('CSS Classes'), icon: 'ph ph-code', placeholder: $empty, sanitizer: $classes),
            'target'         => new Field(t('Open In'), 'select', 'ph ph-crosshair-simple', [
                ''       => t('Same Tab'),
                '_blank' => t('New Tab'),
            ], types: ['link', 'post', 'profile']),
            'visibility'     => new Field(t('Visibility'), 'select', 'ph ph-eye', [
                'all'    => t('Everyone'),
                'guests' => t('Guests'),
                'users'  => t('Logged-In Users'),
                'admins' => t('Administrators'),
            ], 'all', types: ['link', 'post', 'heading', 'separator']),
            // `noopener noreferrer` are added to a link opened in a new tab, see links()
            'rel'            => new Field(t('Link Relations'), 'multiselect', 'ph ph-link-simple', [
                'nofollow'  => 'nofollow',
                'sponsored' => 'sponsored',
                'ugc'       => 'ugc',
            ], [], t('None'), types: ['link', 'post']),
            // the dates of the site timezone, both included, see shown()
            'starts'         => new Field(t('Show From'), 'date', 'ph ph-calendar-blank', placeholder: t('Now')),
            'ends'           => new Field(t('Show Until'), 'date', 'ph ph-calendar-x', placeholder: t('Never')),
        ];

        $languages = self::languages();
        if ($languages) {
            $fields['language'] = new Field(t('Language'), 'select', 'ph ph-translate', [
                '' => t('All Languages'),
                ...$languages,
            ]);
        }

        return array_filter((array) Hook::call('menuItemFields', $fields), fn (mixed $field) => $field instanceof Field);
    }

    /**
     * Attach the menu of each theme location as the `menu-<location>` tree, for the site.
     *
     * @return void
     */
    public static function register(): void
    {
        foreach (self::locationLinks() as $location => $links) {
            $shown = self::shown($links);
            Tree::attach('menu-' . $location, fn (Tree $tree) => $tree->append($shown));
        }
    }

    /**
     * Get the menu of a location as nested arrays for the API: what the requesting visitor sees, the keys
     * a theme reads from the items and `children`.
     *
     * @param string $location
     * @return array<int, array<string, mixed>>|null Null for a location of no menu.
     */
    public static function nested(string $location): ?array
    {
        $links = self::locationLinks()[$location] ?? null;
        if ($links === null) {
            return null;
        }

        $name  = 'menu-api-' . $location;
        $shown = self::shown($links);
        Tree::attach($name, fn (Tree $tree) => $tree->append($shown));

        $export = function (array $items) use (&$export): array {
            return array_map(fn (Tree\Item $item) => [
                ...array_diff_key($item->data, array_flip(['parent_id', 'position', 'capabilities'])),
                'active'   => $item->active,
                'children' => $export($item->children),
            ], $items);
        };

        return $export(Tree::get($name));
    }

    /**
     * Get the links of the menus by location, cached until forgetLinks(): the URLs and titles of the post
     * items are read in a query per post type.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function locationLinks(): array
    {
        $locations = array_keys(self::themeLocations());
        $key       = 'links:' . implode(',', $locations);
        $cached    = Cache::get($key, 'menus');
        if (is_array($cached)) {
            return $cached;
        }

        $links = [];
        foreach (self::all() ?: [] as $menu) {
            $shown = array_intersect($menu->locations, $locations);
            if ($shown !== []) {
                $items = $menu->links();
                foreach ($shown as $location) {
                    $links[$location] = $items;
                }
            }
        }
        Cache::add($key, $links, 'menus', '+1 day');

        return $links;
    }

    /**
     * Drop the cached links of the locations: a menu, a post or the permalinks changed.
     *
     * @return void
     */
    public static function forgetLinks(): void
    {
        Cache::forget('', 'menus');
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
        self::forgetLinks();

        return $this;
    }

    /**
     * Get the items to show: a post item gets the URL and the title of its post, `rel` becomes a string,
     * `visibility` the capabilities of Tree; an item of a missing,
     * unpublished or trashed post is left out, its subitems take its place.
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
            $where = ['id' => $ids, 'status' => 'publish'] + ($type->canTrash ? ['deleted_at' => null] : []);
            foreach (Db::select($type->table, ['id [Int]', 'title'], $where) ?: [] as $row) {
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

            $title   = $item['type'] === 'separator' ? '' : ($item['title'] ?? '');
            $links[] = [
                ...$item,
                'parent_id'    => $parent,
                'title'        => $title !== '' ? $title : (string) ($post['title'] ?? ''),
                'url'          => $post['url'] ?? $item['url'] ?? '',
                'rel'          => implode(' ', $rel),
                'capabilities' => $item['type'] === 'profile' ? ['read'] : self::VISIBILITY[$item['visibility'] ?? 'all'] ?? [],
            ];
        }

        return $links;
    }

    /**
     * Get the links the requested page shows: an item out of its dates, for another language or, with
     * the `guests` visibility, for a signed-in visitor is left out, its subitems take its place; `auth`
     * and `profile` get their title and URL by the visitor.
     *
     * @param array<int, array<string, mixed>> $links
     * @return array<int, array<string, mixed>>
     */
    private static function shown(array $links): array
    {
        $today    = date('Y-m-d');
        $language = self::$language ? (self::$language)() : null;
        $guest    = ! Auth::isLoggedIn();

        $shown   = [];
        $parents = [];
        foreach ($links as $link) {
            $parent = $parents[$link['parent_id']] ?? $link['parent_id'];
            $hidden = (($link['starts'] ?? '') !== '' && $link['starts'] > $today)
                || (($link['ends'] ?? '') !== '' && $link['ends'] < $today)
                || ($language !== null && ($link['language'] ?? '') !== '' && $link['language'] !== $language)
                || (($link['visibility'] ?? 'all') === 'guests' && ! $guest);
            if ($hidden) {
                $parents[$link['id']] = $parent;
                continue;
            }

            $account = match ($link['type']) {
                'auth'    => $guest
                    ? ['title' => t('Sign In'), 'url' => url('sign-in')]
                    : ['title' => t('Sign Out'), 'url' => url('sign-out')],
                'profile' => [
                    'title' => $link['title'] ?: t('Profile'),
                    'url'   => url(Hook::call('dashboardRootSlug', 'dashboard') . '/profile'),
                ],
                default   => [],
            };
            $shown[] = [...$link, 'parent_id' => $parent, ...$account];
        }

        return $shown;
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

            $type = in_array($item['type'] ?? '', self::TYPES, true) ? $item['type'] : 'link';
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

            $isEmpty = match ($type) {
                'post'  => $node['object'] === '' || $node['objectId'] === 0,
                'link'  => ($node['url'] ?? '') === '',
                default => false,
            };
            if ($isEmpty) {
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
