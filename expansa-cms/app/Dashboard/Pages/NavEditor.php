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
        $places    = [];
        foreach ($menus as $menu) {
            $shown = array_intersect($menu->locations, array_keys($locations));
            foreach ($shown as $location) {
                $owners[$location] = $menu->title;
            }
            $places[$menu->id] = implode(', ', array_map(fn (string $location) => $locations[$location], $shown));
        }

        $fields = Menu::itemFields();
        $items  = $current ? self::flatten($current->items, self::posts($current), $current->clicks()) : [];
        $most   = max([1, ...array_column($items, 'clicks')]);
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
                type: $item['type'] === 'post' ? $types[$item['object']] ?? (string) $item['object'] : '',
                kind: $kinds[$item['type']] ?? '',
                problem: $item['problem'],
                clicks: $item['clicks'],
                share: $item['clicks'] / $most,
            ), $items),
            'clicks'    => array_sum(array_column($items, 'clicks')),
            'state'     => [
                'id'        => $current->id ?? 0,
                'title'     => $current->title ?? '',
                'locations' => $current?->locations ?? [],
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
                    'addableText'       => t('Press Enter to create the :name menu.'),
                ],
                'texts'     => [
                    'copied'      => t('Menu copied to the clipboard'),
                    'pasted'      => t('Menu items pasted'),
                    'pasteFailed' => t('The clipboard has no menu items.'),
                ],
            ],
        ];
    }

    /**
     * Get the items of the editor: each with its `depth` instead of `parent_id` and its `clicks`, a post
     * item with the title of its post as `original` and `problem` when the site leaves it out, as
     * Menu::links() does: `deleted` for a missing post, `hidden` for an unpublished or trashed one.
     *
     * @param array                                           $items  Items of the menu, a parent before its subitems.
     * @param array<string, array<int, array<string, mixed>>> $posts  Posts of the items by type and ID, see posts().
     * @param array<string, int>                              $clicks Clicks of the last 30 days by item ID.
     * @return array<int, array<string, mixed>>
     */
    private static function flatten(array $items, array $posts, array $clicks): array
    {
        $list   = [];
        $depths = [];
        foreach ($items as $item) {
            $depth = $depths[$item['id']] = isset($depths[$item['parent_id']]) ? $depths[$item['parent_id']] + 1 : 0;

            $post = $item['type'] === 'post' ? $posts[$item['object']][$item['objectId']] ?? null : null;

            unset($item['parent_id'], $item['position']);
            $list[] = [
                ...$item,
                'depth'    => $depth,
                'original' => $post['title'] ?? '',
                'problem'  => match (true) {
                    $item['type'] !== 'post'                         => '',
                    $post === null                                   => 'deleted',
                    $post['status'] !== 'publish' || $post['trashed'] => 'hidden',
                    default                                          => '',
                },
                'clicks'   => $clicks[$item['id']] ?? 0,
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
     * Read the titles, the statuses and the trash of the posts of the items, a query per post type.
     *
     * @param Menu $menu
     * @return array<string, array<int, array{title: string, status: string, trashed: bool}>>
     */
    private static function posts(Menu $menu): array
    {
        $posts = [];
        foreach ($menu->posts() as $key => $ids) {
            $type = Type::get($key);
            if ($type instanceof Type) {
                $columns = ['id [Int]', 'title', 'status', ...($type->canTrash ? ['deleted_at'] : [])];
                foreach (Db::select($type->table, $columns, ['id' => $ids]) ?: [] as $row) {
                    $posts[$key][$row['id']] = [
                        'title'   => (string) $row['title'],
                        'status'  => (string) $row['status'],
                        'trashed' => ($row['deleted_at'] ?? null) !== null,
                    ];
                }
            }
        }

        return $posts;
    }
}
