<?php

declare(strict_types=1);

namespace App\Api\Comments;

use App\Models\Comment;
use App\Models\User;
use Expansa\Http\Exceptions\ValidationFailed;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Support\Error;

final class CommentsService
{
    /**
     * Fields of a comment a moderator may change; the post, the parent and the origin stay.
     */
    private const array FIELDS = ['content', 'status', 'rating', 'author_name', 'author_email'];

    /**
     * Update a comment from its page.
     *
     * @param Request  $request  `id`, `content`, `status`, `rating` (empty for none), `author_name`, `author_email`.
     * @param Response $response
     * @return Response Notice.
     * @throws ValidationFailed With the fields that are refused.
     */
    public function update(Request $request, Response $response): Response
    {
        $comment = Comment::find((int) ($request->post['id'] ?? 0));
        if (! $comment instanceof Comment) {
            return $response->notify(t('Comment not found'));
        }

        $data = array_intersect_key($request->post, array_flip(self::FIELDS));
        if (array_key_exists('rating', $data)) {
            $data['rating'] = $data['rating'] === '' ? null : max(0, (int) $data['rating']);
        }

        $updated = $comment->update($data);
        if ($updated instanceof Error) {
            throw ValidationFailed::from($updated, t('Could not update the comment. Check the fields.'));
        }

        return $response->notify(t('Comment updated'));
    }

    /**
     * Delete a comment from its page and go back to the list; the comment is kept as soft-deleted.
     *
     * @param Request  $request  `id`.
     * @param Response $response
     * @return Response Notice and redirect.
     */
    public function delete(Request $request, Response $response): Response
    {
        $comment = Comment::find((int) ($request->post['id'] ?? 0));
        if (! $comment instanceof Comment) {
            return $response->notify(t('Comment not found'));
        }

        $comment->delete();

        return $response
            ->notify(t('Comment deleted'))
            ->redirect(url('dashboard/edit?table=comments'), 1200);
    }

    /**
     * Reply to a comment as the current user: an approved comment on the same post.
     *
     * @param Request  $request  `id` of the comment, `content` of the reply.
     * @param Response $response
     * @return Response Notice.
     * @throws ValidationFailed When the reply is refused, e.g. empty.
     */
    public function reply(Request $request, Response $response): Response
    {
        $parent = Comment::find((int) ($request->post['id'] ?? 0));
        if (! $parent instanceof Comment) {
            return $response->notify(t('Comment not found'));
        }

        $user  = User::current();
        $reply = Comment::create([
            'post_id'      => $parent->postId,
            'post_type'    => $parent->postType,
            'parent_id'    => $parent->id,
            'author_id'    => $user?->id ?? 0,
            'author_name'  => $user === null ? '' : ($user->showname ?: $user->login),
            'author_email' => $user->email ?? '',
            'author_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'author_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'locale'       => $parent->locale,
            'status'       => Comment::STATUS_APPROVED,
            'content'      => (string) ($request->post['reply'] ?? ''),
        ]);
        if ($reply instanceof Error) {
            throw ValidationFailed::from($reply, t('Could not send the reply. Check the text.'));
        }

        return $response
            ->notify(t('Reply sent'))
            ->value('#comment-reply-text', '');
    }

    /**
     * Apply a bulk action of the comments table to the selected comments, then reload the table.
     *
     * @param Request  $request  `action`: a status or `delete`; `ids`: comma-separated comment IDs.
     * @param Response $response
     * @return Response Notice and reload.
     */
    public function bulk(Request $request, Response $response): Response
    {
        $action = (string) ($request->post['action'] ?? '');
        $ids    = array_filter(array_map(intval(...), explode(',', (string) ($request->post['ids'] ?? ''))));
        if ($ids === [] || ($action !== 'delete' && ! isset(Comment::statuses()[$action]))) {
            return $response->notify(t('Select comments and an action first.'));
        }

        $count = 0;
        foreach (array_unique($ids) as $id) {
            $comment = Comment::find($id);
            if (! $comment instanceof Comment) {
                continue;
            }

            $isDone = $action === 'delete' ? $comment->delete() > 0 : $comment->update(['status' => $action]) instanceof Comment;
            $count += (int) $isDone;
        }

        return $response
            ->notify(t('Comments changed: :count', $count))
            ->reload(1200);
    }
}
