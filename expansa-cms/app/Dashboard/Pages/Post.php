<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Models\Post as Entry;
use App\Models\User;
use App\Post\Type;
use Expansa\Facades\Db;
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
     * Data of the `post` page: the post of `id` and its type from `type`, pages by default;
     * without `id` a new post, saved on the first submit.
     *
     * @param Request $request
     * @return array{post: Entry, type: Type}
     * @throws NotFound When there is no such type or post.
     */
    public static function data(Request $request): array
    {
        $post = self::post($request->getString('type') ?: 'pages', $request->getInt('id'));

        return [
            'post' => $post,
            'type' => Type::get($post->type),
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
        $post = match (true) {
            $id > 0                   => Entry::get($type, $id),
            Type::get($type) !== null => new Entry(authorId: User::current()->id ?? 0, status: 'draft', type: $type),
            default                   => null,
        };
        if (! $post instanceof Entry) {
            throw new NotFound(t('Item not found'));
        }

        return $post;
    }

    /**
     * Get the posts of the same type a post can be nested in, as a tree by title: a child after its parent
     * with a dash per level; the post and its descendants are left out, nesting in them makes a loop.
     *
     * @param Entry $post
     * @return array<int, string> Titles by ID, `0` for no parent.
     */
    public static function parents(Entry $post): array
    {
        $where = ['ORDER' => ['title' => 'ASC']];
        if (Type::get($post->type)?->canTrash) {
            $where['deleted_at'] = null;
        }

        $rows     = Db::select($post->table, ['id [Int]', 'parent_id [Int]', 'title'], $where) ?: [];
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
