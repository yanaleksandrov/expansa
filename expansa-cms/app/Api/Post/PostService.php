<?php

declare(strict_types=1);

namespace App\Api\Post;

use App\Models\Post;
use App\Post\Status;
use App\Post\Type;
use App\Tables\Posts;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use Expansa\Http\Enums\Notice;
use Expansa\Http\Request;
use Expansa\Http\Response;

final class PostService
{
    /**
     * Columns the editor of a post sends.
     */
    private const array FIELDS = ['title', 'content', 'slug', 'parent_id', 'status', 'discussion', 'password'];

    /**
     * Create a post from the editor of a new one and open its editor.
     *
     * @param Request  $request  POST: `post-type` and the columns, see update(); a draft without `status`.
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        $type = Type::get((string) ($request->post['post-type'] ?? ''));
        if (! $type instanceof Type) {
            return $response->notify(t('Post type is not registered.'), Notice::Error);
        }

        $data   = array_intersect_key($request->post, array_flip(self::FIELDS));
        $status = (string) ($data['status'] ?? '');

        $post = Post::add($type->key, [
            ...$data,
            'title'  => trim((string) ($data['title'] ?? '')) ?: t('Untitled'),
            'status' => array_key_exists($status, Status::getStatuses()) ? $status : 'draft',
        ]);
        if (! $post instanceof Post) {
            return $response->notify(t('Failed to create the item.'), Notice::Error);
        }

        return $response
            ->notify(t('%s created', $type->labelName), Notice::Success)
            ->redirect(url('dashboard/post?type=' . $type->key . '&id=' . $post->id), 600);
    }

    /**
     * Save a post from its editor: the title, content, slug, parent, status, discussion and password.
     *
     * @param Request  $request  POST: `post-type`, `id` and the changed columns.
     * @param Response $response
     * @return Response
     */
    public function update(Request $request, Response $response): Response
    {
        $post = self::find($request);
        if (! $post instanceof Post) {
            return $response->notify(t('Item not found'), Notice::Error);
        }

        if (! $post->update(array_intersect_key($request->post, array_flip(self::FIELDS)))) {
            return $response->notify(t('Failed to save the item.'), Notice::Error);
        }

        // the slug the editor shows becomes the saved one: made from the title or with a suffix of a taken one
        if (isset($request->post['slug']) && (string) $request->post['slug'] !== $post->slug) {
            $response->value('[name="slug"]', $post->slug);
        }

        return $response->notify(t('%s updated', Type::get($post->type)->labelName), Notice::Success);
    }

    /**
     * Create a draft copy of a post with its fields, then reload the list or open the copy.
     *
     * @param Request  $request  POST: `post-type`, `id`, `open` to open the editor of the copy.
     * @param Response $response
     * @return Response
     */
    public function duplicate(Request $request, Response $response): Response
    {
        $post = self::find($request);
        if (! $post instanceof Post) {
            return $response->notify(t('Item not found'), Notice::Error);
        }

        $copy = $post->duplicate();
        if (! $copy instanceof Post) {
            return $response->notify(t('Failed to duplicate the item.'), Notice::Error);
        }

        $response->notify(t('%s duplicated', Type::get($post->type)->labelName), Notice::Success);

        return empty($request->post['open'])
            ? $response->reload(600)
            : $response->redirect(url('dashboard/post?type=' . $copy->type . '&id=' . $copy->id), 600);
    }

    /**
     * Take a post out of the trash and reload the page.
     *
     * @param Request  $request  POST: `post-type`, `id`.
     * @param Response $response
     * @return Response
     */
    public function restore(Request $request, Response $response): Response
    {
        $post = self::find($request);
        if (! $post instanceof Post) {
            return $response->notify(t('Item not found'), Notice::Error);
        }

        if (! $post->restore()) {
            return $response->notify(t('Failed to restore the item.'), Notice::Error);
        }

        return $response
            ->notify(t('%s restored', Type::get($post->type)->labelName), Notice::Success)
            ->reload(600);
    }

    /**
     * Delete a post as its model decides, see Post::delete(): to the trash, or permanently
     * when the type has no trash or the post is in it already; then open the list it was in.
     *
     * @param Request  $request  POST: `post-type`, `id`.
     * @param Response $response
     * @return Response
     */
    public function delete(Request $request, Response $response): Response
    {
        $post = self::find($request);
        if (! $post instanceof Post) {
            return $response->notify(t('Item not found'), Notice::Error);
        }

        $type      = Type::get($post->type);
        $toTrash   = $post->canTrash();
        $fromTrash = $post->isTrashed();
        if (! $post->delete()) {
            return $response->notify(t('Failed to delete the item.'), Notice::Error);
        }

        $notice = $toTrash ? t('%s moved to the trash', $type->labelName) : t('%s deleted permanently', $type->labelName);

        return $response
            ->notify($notice, Notice::Success)
            ->redirect(Posts::url($type, $fromTrash), 600);
    }

    /**
     * Delete every post of a type in the trash permanently and open the list of the type.
     *
     * @param Request  $request  POST: `post-type`.
     * @param Response $response
     * @return Response
     */
    public function emptyTrash(Request $request, Response $response): Response
    {
        $type = Type::get((string) ($request->post['post-type'] ?? ''));
        if (! $type instanceof Type || ! $type->canTrash) {
            return $response->notify(t('Post type has no trash.'), Notice::Error);
        }

        $count = 0;
        foreach (Db::select($type->table, 'id [Int]', ['deleted_at[!]' => null]) ?? [] as $id) {
            $count += (int) Post::get($type->key, $id)?->forceDelete();
        }

        return $response
            ->notify(t(':count items deleted permanently', $count), Notice::Success)
            ->redirect(Posts::url($type), 600);
    }

    /**
     * Apply a bulk action of the posts table to the selected posts, then reload the table:
     * `duplicate`, `trash`, `restore` or `delete` permanently, which skips posts the model would trash.
     *
     * @param Request  $request  POST: `post-type`, `action`, `ids`: comma-separated post IDs.
     * @param Response $response
     * @return Response Notice and reload.
     */
    public function bulk(Request $request, Response $response): Response
    {
        $type   = Type::get((string) ($request->post['post-type'] ?? ''));
        $action = (string) ($request->post['action'] ?? '');
        $ids    = array_unique(array_filter(array_map(intval(...), explode(',', (string) ($request->post['ids'] ?? '')))));
        if (! $type instanceof Type || $ids === [] || ! in_array($action, ['duplicate', 'trash', 'restore', 'delete'], true)) {
            return $response->notify(t('Select items and an action first.'));
        }

        $count = 0;
        foreach ($ids as $id) {
            $post = Post::get($type->key, $id);
            if (! $post instanceof Post) {
                continue;
            }

            $count += (int) match ($action) {
                'duplicate' => $post->duplicate() instanceof Post,
                'trash'     => $post->canTrash() && $post->delete(),
                'restore'   => $post->restore(),
                'delete'    => ! $post->canTrash() && $post->forceDelete(),
            };
        }

        return $response
            ->notify(t('Items changed: :count', $count), $count > 0 ? Notice::Success : Notice::Info)
            ->reload(600);
    }

    /**
     * Get the post of the request by `post-type` and `id`.
     *
     * @param Request $request
     * @return Post|null Null for an unknown type or post.
     */
    private static function find(Request $request): ?Post
    {
        $type = Type::get((string) ($request->post['post-type'] ?? ''));

        return $type instanceof Type ? Post::get($type->key, (int) ($request->post['id'] ?? 0)) : null;
    }
}
