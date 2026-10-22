<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Api\Menus\MenusService;
use App\Dashboard\Values\MenuItem;
use App\Models\Menu;
use App\Models\Values\Field;
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
     * default, none with `?menu=new`), the theme locations, the public post types to add items of
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

        $types = [];
        foreach (Type::fetch(['public' => true, 'showInNavMenus' => true]) as $type) {
            $types[$type->key] = $type->labelNamePlural;
        }

        $locations = Menu::themeLocations();
        $owners    = [];
        foreach ($menus as $menu) {
            foreach (array_intersect($menu->locations, array_keys($locations)) as $location) {
                $owners[$location] = $menu->title;
            }
        }

        $fields = Menu::itemFields();
        $items  = $current ? self::flatten($current->items, self::titles($current)) : [];
        $source = (string) array_key_first($types);
        $posts  = $source !== '' ? new MenusService()->posts($source, '') : [];

        return [
            'menus'     => $menus,
            'current'   => $current,
            'locations' => $locations,
            'owners'    => $owners,
            'types'     => $types,
            'fields'    => $fields,
            'source'    => $source,
            'posts'     => array_column($posts, 'title'),
            'rows'      => array_map(fn (array $item) => new MenuItem(
                label: (string) ($item['title'] ?: $item['original'] ?: $item['url']),
                depth: $item['depth'],
                icon: (string) ($item['icon'] ?? ''),
                type: $item['type'] === 'post' ? $types[$item['object']] ?? (string) $item['object'] : '',
            ), $items),
            'state'     => [
                'id'        => $current->id ?? 0,
                'title'     => $current->title ?? '',
                'locations' => $current?->locations ?? [],
                'items'     => $items,
                'types'     => $types,
                'source'    => $source,
                'posts'     => $source !== '' ? [$source => $posts] : [],
                'fields'    => array_map(fn (Field $field) => [
                    'default'     => $field->default,
                    'types'       => $field->types,
                    'placeholder' => $field->placeholder,
                ], $fields),
                'maxDepth'  => Menu::MAX_DEPTH,
                'texts'     => [
                    'searchPlaceholder' => t('Search'),
                    'searchText'        => t('Nothing found'),
                    'addableText'       => t('Press Enter to create the :name menu.'),
                ],
            ],
        ];
    }

    /**
     * Get the items of the editor: each with its `depth` instead of `id` and `parent_id`, a post item
     * with the title of its post as `original`, or an empty one for a deleted post.
     *
     * @param array                             $items  Items of the menu, a parent before its subitems.
     * @param array<string, array<int, string>> $titles Titles of the posts by type and ID.
     * @return array<int, array<string, mixed>>
     */
    private static function flatten(array $items, array $titles): array
    {
        $list   = [];
        $depths = [];
        foreach ($items as $item) {
            $depth = $depths[$item['id']] = isset($depths[$item['parent_id']]) ? $depths[$item['parent_id']] + 1 : 0;

            unset($item['id'], $item['parent_id'], $item['position']);
            $list[] = [
                ...$item,
                'depth'    => $depth,
                'original' => $item['type'] === 'post' ? $titles[$item['object']][$item['objectId']] ?? '' : '',
            ];
        }

        return $list;
    }

    /**
     * Read the titles of the posts of the items, a query per post type.
     *
     * @param Menu $menu
     * @return array<string, array<int, string>>
     */
    private static function titles(Menu $menu): array
    {
        $titles = [];
        foreach ($menu->posts() as $key => $ids) {
            $type = Type::get($key);
            if ($type instanceof Type) {
                $rows         = Db::select($type->table, ['id [Int]', 'title'], ['id' => $ids]) ?: [];
                $titles[$key] = array_column($rows, 'title', 'id');
            }
        }

        return $titles;
    }
}
