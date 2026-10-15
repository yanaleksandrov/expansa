<?php

declare(strict_types=1);

namespace App\Api\Comments;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Post\Type;
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
     * Delete a comment, kept as soft-deleted, and go back to the list, or with `from` = `post` to the Comments
     * tab of the editor of its post. With `stay`, e.g. from the post editor, the page stays: the comment leaves its thread
     * and its replies take its place.
     *
     * @param Request  $request  `id`, optional `stay` and `from`.
     * @param Response $response
     * @return Response Notice and redirect, or the comment replaced by its replies.
     */
    public function delete(Request $request, Response $response): Response
    {
        $comment = Comment::find((int) ($request->post['id'] ?? 0));
        if (! $comment instanceof Comment) {
            return $response->notify(t('Comment not found'));
        }

        $comment->delete();
        $response->notify(t('Comment deleted'));

        if (empty($request->post['stay'])) {
            $toPost = ($request->post['from'] ?? '') === 'post' && Type::get($comment->postType) !== null;
            $url    = $toPost
                ? 'dashboard/post?type=' . $comment->postType . '&id=' . $comment->postId . '&tab=comments'
                : 'dashboard/edit?table=comments';

            return $response->redirect(url($url), 1200);
        }

        $threads = Comment::groupByParent(Comment::getByPost($comment->postType, (int) $comment->postId));
        $isOpen  = Post::get($comment->postType, (int) $comment->postId)?->isDiscussionOpen() ?? false;
        $replies = array_map(
            fn (Comment $reply) => view('parts/post-comment', [
                'comment' => $reply,
                'replies' => $threads,
                'isOpen'  => $isOpen,
            ])->render(),
            $threads[$comment->id] ?? []
        );

        return $response
            ->replace("#post-comment-$comment->id", implode('', $replies))
            ->update('#post-comments-count', self::count($comment->postType, (int) $comment->postId));
    }

    /**
     * Comment a post from its editor as the current user: an approved comment, added at the end of the list,
     * or of the replies of `parent_id`, a comment of the same post.
     *
     * @param Request  $request  `post-type` and `id` of the post, `content` of the comment, optional `parent_id`.
     * @param Response $response
     * @return Response The comment after the others, the field cleared.
     * @throws ValidationFailed When the comment is refused, e.g. empty.
     */
    public function add(Request $request, Response $response): Response
    {
        $post = Post::get((string) ($request->post['post-type'] ?? ''), (int) ($request->post['id'] ?? 0));
        if (! $post instanceof Post) {
            return $response->notify(t('Item not found'));
        }

        $user = User::current();
        if (! $user instanceof User) {
            return $response->notify(t('You are not allowed to do this.'));
        }

        if (! $post->isDiscussionOpen()) {
            return $response->notify(t('The discussion is closed.'));
        }

        $parentId = (int) ($request->post['parent_id'] ?? 0);
        $parent   = $parentId > 0 ? Comment::find($parentId) : null;
        $isOther = $parent instanceof Comment && ((int) $parent->postId !== $post->id || $parent->postType !== $post->type);
        if ($parent instanceof Error || $isOther) {
            return $response->notify(t('Comment not found'));
        }

        $comment = Comment::createBy($user, [
            'post_id'   => $post->id,
            'post_type' => $post->type,
            'parent_id' => $parent->id ?? 0,
            'locale'    => $parent?->locale,
            'content'   => (string) ($request->post['content'] ?? ''),
        ]);
        if ($comment instanceof Error) {
            throw ValidationFailed::from($comment, t('Could not add the comment. Check the text.'));
        }

        $html = view('parts/post-comment', ['comment' => $comment])->render();
        $response->update('#post-comments-count', self::count($post->type, $post->id));
        if ($parent !== null) {
            return $response
                ->append("#post-comment-replies-$parent->id", $html)
                ->value("#post-comment-reply-$parent->id", '');
        }

        return $response
            ->append('.post-comments-list', $html)
            ->value('#post-comment-text', '');
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

        $user = User::current();
        if (! $user instanceof User) {
            return $response->notify(t('You are not allowed to do this.'));
        }

        $reply = Comment::createBy($user, [
            'post_id'   => $parent->postId,
            'post_type' => $parent->postType,
            'parent_id' => $parent->id,
            'locale'    => $parent->locale,
            'content'   => (string) ($request->post['reply'] ?? ''),
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

    /**
     * Render the number of the comments of a post by status for the side panel of its editor.
     *
     * @param string $postType
     * @param int    $postId
     * @return string
     */
    private static function count(string $postType, int $postId): string
    {
        return view('parts/post-comments-count', ['counts' => Comment::countByStatus($postType, $postId)])->render();
    }
}
