<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Api\Menus\MenusService;
use App\Dashboard\Values\Flag;
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
        $places    = [];
        foreach ($menus as $menu) {
            $shown = array_intersect($menu->locations, array_keys($locations));
            foreach ($shown as $location) {
                $owners[$location] = $menu->title;
            }
            $places[$menu->id] = implode(', ', array_map(fn (string $location) => $locations[$location], $shown));
        }

        $fields = Menu::itemFields();
        $items  = $current ? self::flatten($current->items, self::posts($current)) : [];
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
            'fields'    => $fields,
            'source'    => $source,
            'posts'     => array_column($posts, 'title'),
            'rows'      => array_map(fn (array $item) => new MenuItem(
                label: (string) ($item['title'] ?: $item['original'] ?: $item['url'] ?: '—'),
                depth: $item['depth'],
                icon: (string) ($item['icon'] ?? ''),
                type: $item['type'] === 'post' ? $types[$item['object']] ?? (string) $item['object'] : '',
                problem: $item['problem'],
                flags: self::flags($item, $fields),
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
                    'label'       => $field->label,
                    'type'        => $field->type,
                    'icon'        => $field->icon,
                    'options'     => $field->options,
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
     * with the title of its post as `original` and `problem` when the site leaves it out, as Menu::links()
     * does: `deleted` for a missing post, `hidden` for an unpublished one.
     *
     * @param array                                                     $items Items of the menu, a parent before its subitems.
     * @param array<string, array<int, array{title: string, status: string}>> $posts Posts of the items by type and ID.
     * @return array<int, array<string, mixed>>
     */
    private static function flatten(array $items, array $posts): array
    {
        $list   = [];
        $depths = [];
        foreach ($items as $item) {
            $depth = $depths[$item['id']] = isset($depths[$item['parent_id']]) ? $depths[$item['parent_id']] + 1 : 0;

            $post = $item['type'] === 'post' ? $posts[$item['object']][$item['objectId']] ?? null : null;

            unset($item['id'], $item['parent_id'], $item['position']);
            $list[] = [
                ...$item,
                'depth'    => $depth,
                'original' => $post['title'] ?? '',
                'problem'  => match (true) {
                    $item['type'] !== 'post'      => '',
                    $post === null                => 'deleted',
                    $post['status'] !== 'publish' => 'hidden',
                    default                       => '',
                },
            ];
        }

        return $list;
    }

    /**
     * Get the settings of an item that differ from their defaults: the choices of a field for its type,
     * as the icon of the field with its name and value; the editor finds them the same way.
     *
     * @param array<string, mixed> $item
     * @param array<string, Field> $fields
     * @return Flag[]
     */
    private static function flags(array $item, array $fields): array
    {
        $flags = [];
        foreach ($fields as $name => $field) {
            $value = $item[$name] ?? $field->default;
            if (
                ! in_array($field->type, ['select', 'multiselect'], true)
                || ($field->types && ! in_array($item['type'], $field->types, true))
                || $value === $field->default
                || $value === []
            ) {
                continue;
            }

            $texts   = array_map(fn (mixed $key) => (string) ($field->options[$key] ?? $key), (array) $value);
            $flags[] = new Flag($field->icon, $field->label . ': ' . implode(', ', $texts));
        }

        return $flags;
    }

    /**
     * Read the titles and the statuses of the posts of the items, a query per post type.
     *
     * @param Menu $menu
     * @return array<string, array<int, array{title: string, status: string}>>
     */
    private static function posts(Menu $menu): array
    {
        $posts = [];
        foreach ($menu->posts() as $key => $ids) {
            $type = Type::get($key);
            if ($type instanceof Type) {
                foreach (Db::select($type->table, ['id [Int]', 'title', 'status'], ['id' => $ids]) ?: [] as $row) {
                    $posts[$key][$row['id']] = ['title' => (string) $row['title'], 'status' => (string) $row['status']];
                }
            }
        }

        return $posts;
    }
}
