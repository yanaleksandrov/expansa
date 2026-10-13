<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Models\Comment as Entry;
use App\Post\Type;
use Expansa\Facades\Db;
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
        $comment = Entry::find($id);
        if (! $comment instanceof Entry) {
            throw new NotFound(t('Comment not found'));
        }

        return $comment;
    }

    /**
     * Get the title of the post a comment answers, empty when its type or the post is gone.
     *
     * @param Entry $comment
     * @return string
     */
    public static function postTitle(Entry $comment): string
    {
        $type = Type::get($comment->postType);
        if ($type === null || $comment->postId === 0) {
            return '';
        }

        return (string) Db::get($type->table, 'title', ['id' => $comment->postId]);
    }
}
