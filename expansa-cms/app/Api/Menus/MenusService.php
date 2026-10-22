<?php

declare(strict_types=1);

namespace App\Api\Menus;

use App\Models\Menu;
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

        $menu = $menu->update($this->prepare($request->post));
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

        return $response->notify(t('Menu deleted'), Notice::Success)->redirect('?', 600);
    }

    /**
     * Find published posts by the title, the newest first: of a public type shown in the menus,
     * or of every such type without one; without text the newest ones of the type.
     *
     * @param string $type  Key of the post type, empty for every type.
     * @param string $query
     * @return array<int, array{id: int, title: string, type: string}>
     */
    public function posts(string $type, string $query): array
    {
        $query = trim($query);
        $types = $type === '' ? Type::fetch(['public' => true, 'showInNavMenus' => true]) : array_filter([Type::get($type)]);
        if ($type === '' && $query === '') {
            return [];
        }

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
                $posts[] = [...$row, 'type' => $postType->key];
            }
        }

        return $posts;
    }

    /**
     * Take the title, the locations and the items of the request.
     *
     * @param array<string, mixed> $input
     * @return array{title: string, locations: string[], items: array}
     */
    private function prepare(array $input): array
    {
        $locations = Json::decode((string) ($input['locations'] ?? '[]'), true);
        $items     = Json::decode((string) ($input['items'] ?? '[]'), true);
        $known     = array_keys(Menu::themeLocations());

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
        foreach (array_values(array_filter($items, is_array(...))) as $index => $item) {
            $depth = min(max(0, (int) ($item['depth'] ?? 0)), count($stack));
            $stack = [...array_slice($stack, 0, $depth), (string) ($index + 1)];

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
