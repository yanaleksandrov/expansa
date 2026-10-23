<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Api\Menus\MenusService;
use App\Dashboard\Values\MenuItem;
use App\Models\Menu;
use App\Models\Values\Field;
use App\Post\Taxonomy;
use App\Post\Type;
use Expansa\Facades\Db;
use Expansa\Http\Request;

/**
 * Editor of the navigation menus of the site: the menus, their items and the theme locations
 * they are shown in.
 *
 * @package App\Dashboard
 */
final class NavEditor
{
    /**
     * Data of the `nav-editor` page: the menus, the one of `menu` being edited (the first one by
     * default, none with `?menu=new`), the theme locations, the public post types and taxonomies to add items of
     * (with `showInNavMenus`) and the fields of an item; the editor gets their defaults and the types
     * of items they are for. The rows of the items and the posts of the first type, open in the sources,
     * are rendered before the editor starts.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public static function data(Request $request): array
    {
        $menus   = Menu::all() ?: [];
        $isNew   = $request->getString('menu') === 'new';
        $menuId  = $request->getInt('menu');
        $current = $isNew ? null : array_find($menus, fn (Menu $menu) => $menuId === 0 || $menu->id === $menuId);

        // the sources of the items: the post types, then the taxonomies as `term:<key>`
        $types = [];
        foreach (Type::filter(['public' => true, 'showInNavMenus' => true]) as $type) {
            $types[$type->key] = $type->labelNamePlural;
        }
        foreach (Taxonomy::filter(['public' => true, 'showInNavMenus' => true]) as $taxonomy) {
            $types["term:$taxonomy->key"] = $taxonomy->labelNamePlural;
        }

        $locations = Menu::themeLocations();
        $owners    = [];
        $places    = [];
        foreach ($menus as $menu) {
            $shown = array_intersect($menu->locations, array_keys($locations));
            foreach ($shown as $location) {
                $owners[$location] = $menu->title;
            }
            $places[$menu->id] = implode(', ', array_map(fn (string $location) => $locations[$location], $shown));
        }

        $fields = Menu::itemFields();
        $items  = $current ? self::flatten($current->items, self::entries($current)) : [];
        $kinds  = [
            'link'      => t('Custom Link'),
            'heading'   => t('Heading'),
            'separator' => t('Separator'),
            'auth'      => t('Sign In / Sign Out'),
            'profile'   => t('Profile'),
        ];
        $source = (string) array_key_first($types);
        $posts  = $source !== '' ? new MenusService()->posts($source, '') : [];

        return [
            'menus'     => $menus,
            'current'   => $current,
            'locations' => $locations,
            'owners'    => $owners,
            'places'    => $places,
            'orphans'   => $current ? array_values(array_diff($current->locations, array_keys($locations))) : [],
            'types'     => $types,
            'kinds'     => array_diff_key($kinds, ['link' => true]),
            'fields'    => $fields,
            'source'    => $source,
            'posts'     => array_column($posts, 'title'),
            'rows'      => array_map(fn (array $item) => new MenuItem(
                label: self::label($item, $kinds),
                depth: $item['depth'],
                icon: (string) ($item['icon'] ?? ''),
                type: match ($item['type']) {
                    'post'  => $types[$item['object']] ?? (string) $item['object'],
                    'term'  => $types['term:' . $item['object']] ?? (string) $item['object'],
                    default => '',
                },
                kind: $kinds[$item['type']] ?? '',
                problem: $item['problem'],
            ), $items),
            'state'     => [
                'id'        => $current->id ?? 0,
                'title'     => $current->title ?? '',
                'locations' => $current->locations ?? [],
                'items'     => $items,
                'types'     => $types,
                'kinds'     => $kinds,
                'source'    => $source,
                'posts'     => $source !== '' ? [$source => $posts] : [],
                'fields'    => array_map(fn (Field $field) => [
                    'label'       => $field->label,
                    'type'        => $field->type,
                    'icon'        => $field->icon,
                    'options'     => $field->options,
                    'default'     => $field->default,
                    'types'       => $field->types,
                    'placeholder' => $field->placeholder,
                ], $fields),
                'maxDepth'  => Menu::MAX_DEPTH,
                'select'    => [
                    'searchPlaceholder' => t('Search'),
                    'searchText'        => t('Nothing found'),
                ],
                'texts'     => [
                    'copied'        => t('Menu copied to the clipboard'),
                    'pasted'        => t('Menu items pasted'),
                    'pasteFailed'   => t('The clipboard has no menu items.'),
                    'deleteMessage' => t('The menu and its items will be deleted permanently.'),
                    'deleteAction'  => t('Delete Permanently'),
                ],
            ],
        ];
    }

    /**
     * Get the items of the editor: each with its `depth` instead of `parent_id`, a post or a term
     * item with the title of its post or the name of its term as `original` and `problem` when the site
     * leaves it out, as Menu::links() does: `deleted` for a missing entry, `hidden` for an unpublished or trashed post.
     *
     * @param array                                                          $items   Items of the menu, parents first.
     * @param array<string, array<string, array<int, array<string, mixed>>>> $entries Posts and terms, see entries().
     * @return array<int, array<string, mixed>>
     */
    private static function flatten(array $items, array $entries): array
    {
        $list   = [];
        $depths = [];
        foreach ($items as $item) {
            $depth = $depths[$item['id']] = isset($depths[$item['parent_id']]) ? $depths[$item['parent_id']] + 1 : 0;

            $isEntry = in_array($item['type'], Menu::ENTRIES, true);
            $entry   = $isEntry ? $entries[$item['type']][$item['object']][$item['objectId']] ?? null : null;

            unset($item['parent_id'], $item['position']);
            $list[] = [
                ...$item,
                'depth'    => $depth,
                'original' => $entry['title'] ?? '',
                'problem'  => match (true) {
                    ! $isEntry                                          => '',
                    $entry === null                                     => 'deleted',
                    $entry['status'] !== 'publish' || $entry['trashed'] => 'hidden',
                    default                                             => '',
                },
            ];
        }

        return $list;
    }

    /**
     * Get the text of the row of an item, as label() of the editor: its label, the title of its post,
     * its URL or the name of its type.
     *
     * @param array<string, mixed>  $item
     * @param array<string, string> $kinds Names of the types that are not posts.
     * @return string
     */
    private static function label(array $item, array $kinds): string
    {
        return (string) (($item['title'] ?? '') ?: $item['original'] ?: ($item['url'] ?? '') ?: ($kinds[$item['type']] ?? '—'));
    }

    /**
     * Read the titles, the statuses and the trash of the posts of the items, a query per post type,
     * and the names of the terms, a query per taxonomy; a term is always published. The entries
     * are keyed by the type of the item, the post type or the taxonomy and the ID.
     *
     * @param Menu $menu
     * @return array<string, array<string, array<int, array{title: string, status: string, trashed: bool}>>>
     */
    private static function entries(Menu $menu): array
    {
        $entries = ['post' => [], 'term' => []];
        foreach ($menu->posts() as $key => $ids) {
            $type = Type::get($key);
            if ($type instanceof Type) {
                $columns = ['id [Int]', 'title', 'status', ...($type->canTrash ? ['deleted_at'] : [])];
                foreach (Db::select($type->table, $columns, ['id' => $ids]) ?: [] as $row) {
                    $entries['post'][$key][$row['id']] = [
                        'title'   => (string) $row['title'],
                        'status'  => (string) $row['status'],
                        'trashed' => ($row['deleted_at'] ?? null) !== null,
                    ];
                }
            }
        }

        foreach ($menu->terms() as $key => $ids) {
            foreach (Db::select('terms', ['id [Int]', 'name'], ['id' => $ids, 'taxonomy' => $key]) ?: [] as $row) {
                $entries['term'][$key][$row['id']] = [
                    'title'   => (string) $row['name'],
                    'status'  => 'publish',
                    'trashed' => false,
                ];
            }
        }

        return $entries;
    }
}
