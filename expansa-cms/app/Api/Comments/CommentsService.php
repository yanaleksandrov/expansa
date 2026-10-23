<?php

declare(strict_types=1);

namespace App\Api\Comments;

use App\Models\Comment;
use App\Models\Option;
use App\Models\Post;
use App\Models\User;
use App\Post\Type;
use App\Support\Dates;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Http\Enums\Notice;
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
     * @return Response Notice and the new update time on the comment page.
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

        // the "Updated" fact of the comment page, in the format it is rendered with
        $time = htmlspecialchars(Dates::format($updated->updatedAt));

        return $response
            ->notify(t('Comment updated'))
            ->update('#comment-updated', sprintf('<span class="post-editor-pill" title="%1$s">%1$s</span>', $time));
    }

    /**
     * Delete a comment, kept as soft-deleted, and go back to the list, or with `from` = `post` to the Comments
     * tab of the editor of its post; with `from` = `list` only the notice, the list filters its rows again. With `stay`,
     * e.g. from the post editor, the page stays: the comment leaves its thread and its replies take its place.
     *
     * @param Request  $request  `id`, optional `stay` and `from`.
     * @param Response $response
     * @return Response Notice, with a redirect or the comment replaced by its replies.
     */
    public function delete(Request $request, Response $response): Response
    {
        $comment = Comment::find((int) ($request->post['id'] ?? 0));
        if (! $comment instanceof Comment) {
            return $response->notify(t('Comment not found'));
        }

        $comment->delete();
        $response = $response->notify(t('Comment deleted'));
        $from     = $request->post['from'] ?? '';
        if ($from === 'list') {
            return $response;
        }

        if (empty($request->post['stay'])) {
            $toPost = $from === 'post' && Type::get($comment->postType) !== null;
            $url    = $toPost
                ? 'dashboard/post?type=' . $comment->postType . '&id=' . $comment->postId . '&tab=comments'
                : 'dashboard/edit?table=comments';

            return $response->redirect(url($url), 1200);
        }

        $threads = Comment::groupByParent(Comment::getByPost($comment->postType, (int) $comment->postId));
        $isOpen  = Post::get($comment->postType, (int) $comment->postId)?->isDiscussionOpen() ?? false;
        $replies = array_map(
            fn (Comment $reply) => view('parts/post-comment', [
                'comment'  => $reply,
                'replies'  => $threads,
                'isOpen'   => $isOpen,
                'canEdit'  => true,
                'maxDepth' => Comment::maxDepth(),
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

        if ($parent instanceof Comment && $parent->depth() >= Comment::maxDepth()) {
            return $response->notify(t('This comment does not take replies.'));
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

        $html = view('parts/post-comment', [
            'comment'  => $comment,
            'canEdit'  => true,
            'depth'    => $parent instanceof Comment ? $parent->depth() + 1 : 1,
            'maxDepth' => Comment::maxDepth(),
        ])->render();
        $response = $response->update('#post-comments-count', self::count($post->type, $post->id));
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
     * Comment a post from the site as a visitor or a signed-in user, see Comment::submit(); ten in ten minutes
     * from one address. A filled Comment::TRAP field or a form sent sooner than `comments.fill_time` seconds
     * by its Comment::STAMP gets the notice of a held comment, nothing is saved.
     *
     * @param Request  $request  `post-type` and `id` of the post, `content`, optional `parent_id`,
     *                           `author_name` and `author_email` of a guest, the empty Comment::TRAP and Comment::STAMP.
     * @param Response $response
     * @return Response Notice whether the comment is published or waits for moderation.
     * @throws ValidationFailed When the comment is refused, e.g. without a name.
     */
    public function submit(Request $request, Response $response): Response
    {
        Auth::limit('comment-submit:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 600);

        $fillTime = max(0, (int) Option::get('comments.fill_time', 3));
        $age      = $fillTime > 0 ? Comment::stampAge((string) ($request->post[Comment::STAMP] ?? '')) : 0;
        if ($age === null) {
            return $response->notify(t('The form is out of date. Reload the page and try again.'), Notice::Error);
        }

        // the bot must not learn that it was caught
        if (($request->post[Comment::TRAP] ?? '') !== '' || $age < $fillTime) {
            return $response->notify(t('Your comment is awaiting moderation.'));
        }

        $comment = Comment::submit([
            'post_type'    => (string) ($request->post['post-type'] ?? ''),
            'post_id'      => (int) ($request->post['id'] ?? 0),
            'parent_id'    => (int) ($request->post['parent_id'] ?? 0),
            'author_name'  => (string) ($request->post['author_name'] ?? ''),
            'author_email' => (string) ($request->post['author_email'] ?? ''),
            'content'      => (string) ($request->post['content'] ?? ''),
        ], User::current());
        if ($comment instanceof Error) {
            throw ValidationFailed::from($comment, t('Could not add the comment. Check the text.'));
        }

        return $response->notify($comment->status === Comment::STATUS_APPROVED
            ? t('Your comment is published.')
            : t('Your comment is awaiting moderation.'));
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

        if (! $parent->canReply()) {
            return $response->notify(t('This comment does not take replies.'));
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
     * Change the status of a comment by a quick action. With `stay`, from the post editor, the comment is
     * rendered again in its place with the counts of the post; the table reloads its rows by itself.
     *
     * @param Request  $request  `id`, `status` and optional `stay`.
     * @param Response $response
     * @return Response Notice, and the comment in the post editor.
     */
    public function status(Request $request, Response $response): Response
    {
        $comment  = Comment::find((int) ($request->post['id'] ?? 0));
        $status   = (string) ($request->post['status'] ?? '');
        $statuses = Comment::statuses();
        if (! $comment instanceof Comment || ! isset($statuses[$status])) {
            return $response->notify(t('Comment not found'));
        }

        $updated = $comment->update(['status' => $status]);
        if (! $updated instanceof Comment) {
            return $response->notify(t('Could not update the comment. Check the fields.'));
        }

        $response = $response->notify(t('Comment status: :status', $statuses[$status]));
        if (empty($request->post['stay'])) {
            return $response;
        }

        $html = view('parts/post-comment', [
            'comment'  => $updated,
            'replies'  => Comment::groupByParent(Comment::getByPost($updated->postType, (int) $updated->postId)),
            'isOpen'   => Post::get($updated->postType, (int) $updated->postId)?->isDiscussionOpen() ?? false,
            'canEdit'  => true,
            'depth'    => $updated->depth(),
            'maxDepth' => Comment::maxDepth(),
        ])->render();

        return $response
            ->replace("#post-comment-$updated->id", $html)
            ->update('#post-comments-count', self::count($updated->postType, (int) $updated->postId));
    }

    /**
     * Take a comment out of the trash; the table filters its rows again by itself.
     *
     * @param Request  $request  `id`.
     * @param Response $response
     * @return Response Notice.
     */
    public function restore(Request $request, Response $response): Response
    {
        $comment = Comment::findTrashed((int) ($request->post['id'] ?? 0));
        if (! $comment instanceof Comment) {
            return $response->notify(t('Comment not found'));
        }

        $comment->restore();

        return $response->notify(t('Comment restored'), Notice::Success);
    }

    /**
     * Delete a comment in the trash permanently; the table filters its rows again by itself.
     *
     * @param Request  $request  `id`.
     * @param Response $response
     * @return Response Notice.
     */
    public function forceDelete(Request $request, Response $response): Response
    {
        $comment = Comment::findTrashed((int) ($request->post['id'] ?? 0));
        if (! $comment instanceof Comment) {
            return $response->notify(t('Comment not found'));
        }

        $comment->forceDelete();

        return $response->notify(t('Comment deleted permanently'), Notice::Success);
    }

    /**
     * Delete every comment in the trash permanently and open the list.
     *
     * @param Request  $request
     * @param Response $response
     * @return Response Notice and redirect.
     */
    public function emptyTrash(Request $request, Response $response): Response
    {
        $count = 0;
        foreach (Db::select('comments', 'id [Int]', ['deleted_at[!]' => null]) ?? [] as $id) {
            $count += (int) Comment::findTrashed($id)?->forceDelete();
        }

        return $response
            ->notify(t(':count items deleted permanently', $count), Notice::Success)
            ->redirect(url('dashboard/edit?table=comments'), 600);
    }

    /**
     * Apply a bulk action of the comments table to the selected comments, then reload the table:
     * a status or `delete` to the trash, `restore` or `force-delete` of the comments in the trash.
     *
     * @param Request  $request  `action`; `ids`: comma-separated comment IDs.
     * @param Response $response
     * @return Response Notice and reload.
     */
    public function bulk(Request $request, Response $response): Response
    {
        $action  = (string) ($request->post['action'] ?? '');
        $ids     = array_filter(array_map(intval(...), explode(',', (string) ($request->post['ids'] ?? ''))));
        $isTrash = in_array($action, ['restore', 'force-delete'], true);
        if ($ids === [] || (! $isTrash && $action !== 'delete' && ! isset(Comment::statuses()[$action]))) {
            return $response->notify(t('Select comments and an action first.'));
        }

        $count = 0;
        foreach (array_unique($ids) as $id) {
            $comment = $isTrash ? Comment::findTrashed($id) : Comment::find($id);
            if (! $comment instanceof Comment) {
                continue;
            }

            $isDone = match ($action) {
                'delete'       => $comment->delete() > 0,
                'restore'      => $comment->restore() > 0,
                'force-delete' => $comment->forceDelete() > 0,
                default        => $comment->update(['status' => $action]) instanceof Comment,
            };
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
