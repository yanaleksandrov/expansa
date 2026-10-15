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
     * Get the posts of the same type a post can be nested in: all but itself, by title.
     *
     * @param Entry $post
     * @return array<int, string> Titles by ID, `0` for no parent.
     */
    public static function parents(Entry $post): array
    {
        $rows = Db::select($post->table, ['id [Int]', 'title'], [
            'id[!]' => $post->id,
            'ORDER' => ['title' => 'ASC'],
        ]);

        return [0 => t('No parent')] + array_column($rows ?: [], 'title', 'id');
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
