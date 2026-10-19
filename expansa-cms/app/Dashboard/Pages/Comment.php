<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Models\Comment as Entry;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Request;

/**
 * Page of a comment for a moderator: its text, status and author, the post it answers.
 *
 * @package App\Dashboard
 */
final class Comment
{
    /**
     * Comments read in the request by ID: the page data and the `comment-editor` form ask for the same one.
     *
     * @var array<int, Entry>
     */
    private static array $comments = [];

    /**
     * Data of the `comment` page: the comment of `id`.
     *
     * @param Request $request
     * @return array{comment: Entry}
     * @throws NotFound When there is no such comment.
     */
    public static function data(Request $request): array
    {
        return ['comment' => self::comment($request->getInt('id'))];
    }

    /**
     * Get a comment by its ID.
     *
     * @param int $id
     * @return Entry
     * @throws NotFound When there is no such comment.
     */
    public static function comment(int $id): Entry
    {
        if (isset(self::$comments[$id])) {
            return self::$comments[$id];
        }

        $comment = Entry::find($id);
        if (! $comment instanceof Entry) {
            throw new NotFound(t('Comment not found'));
        }

        return self::$comments[$id] = $comment;
    }

    /**
     * Get the title of the post a comment answers, empty when its type or the post is gone.
     *
     * @param Entry $comment
     * @return string
     */
    public static function postTitle(Entry $comment): string
    {
        return $comment->getPost()?->title ?? '';
    }
}
