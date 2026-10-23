<?php

declare(strict_types=1);

namespace App\Api\Menus;

use App\Models\Menu;
use App\Post\Taxonomy;
use App\Post\Type;
use Expansa\Facades\Db;
use Expansa\Facades\Json;
use Expansa\Http\Enums\Notice;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Support\Error;

/**
 * Menus of the `nav-editor` page. The editor sends the items as a JSON list in the order of the
 * page, each with its `depth`, they get the `id` and `parent_id` of Tree here. The answers are
 * `$ajax` fragments.
 *
 * @package App\Api
 */
final class MenusService
{
    /**
     * Posts found for the panel of a post type, the list stays short.
     */
    private const int LIMIT = 20;

    public function create(Request $request, Response $response): Response
    {
        $menu = Menu::create($this->prepare($request->post));
        if ($menu instanceof Error) {
            return $response->notify($this->flatten($menu), Notice::Error);
        }

        return $response->notify(t('Menu created'), Notice::Success)->redirect('?menu=' . $menu->id, 600);
    }

    public function update(Request $request, Response $response): Response
    {
        $menu = Menu::find((int) ($request->post['id'] ?? 0));
        if ($menu instanceof Error) {
            return $response->notify($this->flatten($menu), Notice::Error);
        }

        $menu = $menu->update($this->prepare($request->post, $menu->locations));
        if ($menu instanceof Error) {
            return $response->notify($this->flatten($menu), Notice::Error);
        }

        return $response->notify(t('Menu saved'), Notice::Success);
    }

    public function delete(Request $request, Response $response): Response
    {
        $menu = Menu::find((int) ($request->post['id'] ?? 0));
        if ($menu instanceof Error) {
            return $response->notify($this->flatten($menu), Notice::Error);
        }

        $menu->delete();
        Menu::forgetLinks();

        return $response->notify(t('Menu deleted'), Notice::Success)->redirect('?', 600);
    }

    /**
     * Answer the menu of a theme location as the visitor of the request sees it.
     *
     * @param Response $response
     * @param string   $location
     * @return Response
     */
    public function location(Response $response, string $location): Response
    {
        $items = Menu::nested($location);
        if ($items === null) {
            return $response->json(['message' => t('Menu not found')], 404);
        }

        return $response->json(['location' => $location, 'items' => $items]);
    }

    /**
     * Find the entries of a source of the editor: published posts by the title, the newest first, of a public
     * type shown in the menus, or the terms by the name of such a taxonomy, `term:<key>`; every source without one.
     * Without text the newest posts or the first terms of the source. `type` of a found entry is its source,
     * `kind` the type of its item and `object` the post type or the taxonomy.
     *
     * @param string $type  Key of the source, empty for every one.
     * @param string $query
     * @return array<int, array{id: int, title: string, type: string, kind: string, object: string}>
     */
    public function posts(string $type, string $query): array
    {
        $query = trim($query);
        if ($type === '' && $query === '') {
            return [];
        }

        $types = $type === '' ? Type::filter(['public' => true, 'showInNavMenus' => true]) : array_filter([Type::get($type)]);

        $posts = [];
        foreach ($types as $postType) {
            if (! $postType->public || ! $postType->showInNavMenus) {
                continue;
            }

            $where = [
                'status' => 'publish',
                'ORDER'  => ['created_at' => 'DESC'],
                'LIMIT'  => self::LIMIT,
            ];
            if ($query !== '') {
                $where['title[~]'] = $query;
            }
            if ($postType->canTrash) {
                $where['deleted_at'] = null;
            }

            foreach (Db::select($postType->table, ['id [Int]', 'title'], $where) ?: [] as $row) {
                $posts[] = [...$row, 'type' => $postType->key, 'kind' => 'post', 'object' => $postType->key];
            }
        }

        $taxonomies = match (true) {
            $type === ''                    => Taxonomy::filter(),
            str_starts_with($type, 'term:') => array_filter([Taxonomy::get(substr($type, 5))]),
            default                         => [],
        };
        foreach ($taxonomies as $taxonomy) {
            if (! $taxonomy->public || ! $taxonomy->showInNavMenus) {
                continue;
            }

            $where = [
                'taxonomy' => $taxonomy->key,
                'ORDER'    => ['name' => 'ASC'],
                'LIMIT'    => self::LIMIT,
            ];
            if ($query !== '') {
                $where['name[~]'] = $query;
            }

            foreach (Db::select('terms', ['id [Int]', 'name'], $where) ?: [] as $row) {
                $posts[] = [
                    'id'     => $row['id'],
                    'title'  => $row['name'],
                    'type'   => "term:$taxonomy->key",
                    'kind'   => 'term',
                    'object' => $taxonomy->key,
                ];
            }
        }

        return $posts;
    }

    /**
     * Take the title, the locations and the items of the request: a location of the theme, or one
     * the menu already has, though the theme no longer declares it.
     *
     * @param array<string, mixed> $input
     * @param string[]             $kept  Locations of the menu before the update.
     * @return array{title: string, locations: string[], items: array}
     */
    private function prepare(array $input, array $kept = []): array
    {
        $locations = Json::decode((string) ($input['locations'] ?? '[]'), true);
        $items     = Json::decode((string) ($input['items'] ?? '[]'), true);
        $known     = [...array_keys(Menu::themeLocations()), ...$kept];

        return [
            'title'     => (string) ($input['title'] ?? ''),
            'locations' => array_values(array_intersect(is_array($locations) ? $locations : [], $known)),
            'items'     => $this->parents(is_array($items) ? $items : []),
        ];
    }

    /**
     * Turn the flat list of the editor into the items Menu stores: an item is a child of the last one
     * above it that is one level higher, a level skipped in the list is closed up.
     *
     * @param array<int, mixed> $items Items in the order of the page, each with its `depth`.
     * @return array<int, array<string, mixed>>
     */
    private function parents(array $items): array
    {
        $list  = [];
        $stack = [];
        $taken = [];
        foreach (array_values(array_filter($items, is_array(...))) as $item) {
            // the ID of the editor stays; a new or a repeated one is generated
            $id = (string) ($item['id'] ?? '');
            if (! preg_match('/^[A-Za-z0-9_-]{1,36}$/', $id) || isset($taken[$id])) {
                $id = bin2hex(random_bytes(6));
            }
            $taken[$id] = true;

            $depth = min(max(0, (int) ($item['depth'] ?? 0)), count($stack));
            $stack = [...array_slice($stack, 0, $depth), $id];

            unset($item['depth']);
            $list[] = [
                ...$item,
                'id'        => $stack[$depth],
                'parent_id' => $stack[$depth - 1] ?? '',
            ];
        }

        return $list;
    }

    /**
     * Join the messages of an error, a string or the messages of the fields, into one string.
     *
     * @param Error $error
     * @return string
     */
    private function flatten(Error $error): string
    {
        $messages = [];
        $nested   = $error->messages;

        array_walk_recursive($nested, function ($message) use (&$messages) {
            $messages[] = $message;
        });

        return $messages ? implode(' ', $messages) : t('Something went wrong.');
    }
}
