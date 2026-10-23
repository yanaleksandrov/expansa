<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Models\Post as Entry;
use App\Models\User;
use App\Post\Type;
use Expansa\Facades\Db;
use Expansa\Facades\Role;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Request;

/**
 * Editor of a single post: its title, content and slug, the status, parent and discussion in the side panel.
 *
 * @package App\Dashboard
 */
final class Post
{
    /**
     * Number of options a select of the editor shows: when there are more, it searches on the server.
     */
    public const int OPTIONS_LIMIT = 20;

    /**
     * Posts read in the request by `type:id`: the page data and the `post-editor` form ask for the same one.
     *
     * @var array<string, Entry>
     */
    private static array $posts = [];

    /**
     * Data of the `post` page: the post of `id` and its type from `type`, pages by default,
     * without `id` a new post, saved on the first submit; and whether the viewer can add a comment to it.
     *
     * @param Request $request
     * @return array{post: Entry, type: Type, canComment: bool}
     * @throws NotFound When there is no such type or post.
     */
    public static function data(Request $request): array
    {
        $post = self::post($request->getString('type') ?: 'pages', $request->getInt('id'));

        return [
            'post'       => $post,
            'type'       => Type::get($post->type),
            'canComment' => $post->id > 0 && $post->isDiscussionOpen() && (User::current()?->can('manage_comments') ?? false),
        ];
    }

    /**
     * Get a post by its type and ID, a new unsaved draft of the type for the ID 0.
     *
     * @param string $type
     * @param int    $id
     * @return Entry
     * @throws NotFound When there is no such type or post.
     */
    public static function post(string $type, int $id): Entry
    {
        $key = $type . ':' . $id;
        if (isset(self::$posts[$key])) {
            return self::$posts[$key];
        }

        $post = match (true) {
            $id > 0                   => Entry::get($type, $id),
            Type::get($type) !== null => new Entry(authorId: User::current()->id ?? 0, status: 'draft', type: $type),
            default                   => null,
        };
        if (! $post instanceof Entry) {
            throw new NotFound(t('Item not found'));
        }

        return self::$posts[$key] = $post;
    }

    /**
     * Get the posts of the same type a post can be nested in; the post and its descendants are left out,
     * nesting in them makes a loop. All of them as a tree by title, a dash per level, when there are no more
     * than $limit and no search; else the first ones by title as a list, one more than $limit tells of the rest.
     *
     * @param Entry  $post
     * @param string $search Text of the title, empty for the first ones.
     * @param int    $limit
     * @return array<int, string> Titles by ID; `0` for no parent and the current parent without a search.
     */
    public static function parents(Entry $post, string $search = '', int $limit = self::OPTIONS_LIMIT): array
    {
        $where = [
            'ORDER' => ['title' => 'ASC'],
            'LIMIT' => $limit + 1,
        ];
        if (Type::get($post->type)?->canTrash) {
            $where['deleted_at'] = null;
        }

        $rows = $search === '' ? Db::select($post->table, ['id [Int]', 'parent_id [Int]', 'title'], $where) ?: [] : [];
        if ($search !== '' || count($rows) > $limit) {
            $where['id[!]'] = [$post->id, ...self::descendants($post)];
            if ($search !== '') {
                $where['title[~]'] = $search;
            }

            $parents = [];
            foreach (Db::select($post->table, ['id [Int]', 'title'], $where) ?: [] as $row) {
                $parents[$row['id']] = $row['title'] !== '' ? $row['title'] : '#' . $row['id'];
            }

            if ($search !== '') {
                return $parents;
            }

            // the saved parent stays to choose even when it is not among the first ones
            if ($post->parentId > 0 && ! isset($parents[$post->parentId])) {
                $title   = (string) Db::get($post->table, 'title', ['id' => $post->parentId]);
                $parents = [$post->parentId => $title !== '' ? $title : '#' . $post->parentId] + $parents;
            }

            return [0 => t('No parent')] + $parents;
        }

        $ids      = array_flip(array_column($rows, 'id'));
        $children = [];
        foreach ($rows as $row) {
            // a child of a deleted or trashed post is shown at the top
            $children[isset($ids[$row['parent_id']]) ? $row['parent_id'] : 0][] = $row;
        }

        $parents = [0 => t('No parent')];
        $walk    = function (int $parentId, int $depth) use (&$walk, &$parents, $children, $post): void {
            foreach ($children[$parentId] ?? [] as $row) {
                if ($row['id'] === $post->id) {
                    continue;
                }

                $parents[$row['id']] = str_repeat('— ', $depth) . ($row['title'] !== '' ? $row['title'] : '#' . $row['id']);
                $walk($row['id'], $depth + 1);
            }
        };
        $walk(0, 0);

        return $parents;
    }

    /**
     * Get the users who may be authors, see Entry::AUTHOR_PERMISSION, by the login or the display name,
     * sorted by the name; one more than $limit tells of the rest.
     *
     * @param string $search Text of the login or the name, empty for the first ones.
     * @param int    $limit
     * @return array<int, string> Names by ID.
     */
    public static function authors(string $search = '', int $limit = self::OPTIONS_LIMIT): array
    {
        $roles = Role::withPermission(Entry::AUTHOR_PERMISSION);
        if ($roles === []) {
            return [];
        }

        // a role is a quoted name in the JSON list; an underscore in it would stop LIKE from adding the percents
        $where = [
            'deleted_at' => null,
            'roles[~]'   => array_map(fn (string $role) => '%"' . $role . '"%', $roles),
            'ORDER'      => ['showname' => 'ASC'],
            'LIMIT'      => $limit + 1,
        ];
        if ($search !== '') {
            $where['OR'] = [
                'login[~]'    => $search,
                'showname[~]' => $search,
            ];
        }

        $authors = [];
        foreach (Db::select('users', ['id [Int]', 'login', 'showname'], $where) ?: [] as $user) {
            $authors[$user['id']] = $user['showname'] ?: $user['login'];
        }

        return $authors;
    }

    /**
     * Get the IDs of the descendants of a post, a level of children per query.
     *
     * @param Entry $post
     * @return int[]
     */
    private static function descendants(Entry $post): array
    {
        $ids   = [];
        $level = $post->id > 0 ? [$post->id] : [];
        while ($level !== []) {
            $level = array_values(array_diff(Db::select($post->table, 'id [Int]', ['parent_id' => $level]) ?: [], $ids));
            $ids   = [...$ids, ...$level];
        }

        return $ids;
    }

    /**
     * Get the name of the post author, empty when the user is gone.
     *
     * @param Entry $post
     * @return string
     */
    public static function author(Entry $post): string
    {
        $user = $post->authorId > 0 ? User::find($post->authorId) : null;

        return $user instanceof User ? ($user->showname ?: $user->login) : '';
    }
}
