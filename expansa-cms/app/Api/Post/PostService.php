<?php

declare(strict_types=1);

namespace App\Api\Post;

use App\Models\Post;
use App\Post\Type;
use Expansa\Facades\Safe;
use Expansa\Http\Enums\Notice;
use Expansa\Http\Request;
use Expansa\Http\Response;

final class PostService
{
    /**
     * Create a draft of a post type and open the list of the type.
     *
     * @param Request  $request  POST: `post-type`, optional `title` and `status`.
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        $data = Safe::data($request->post, [
            'post-type' => 'text',
            'title'     => 'text',
            'status'    => 'text:draft',
        ])->apply();

        $type = Type::get($data['post-type']);
        if (! $type instanceof Type) {
            return $response->notify(t('Post type is not registered.'), Notice::Error);
        }

        $post = Post::add($type->key, [
            'title'  => $data['title'] ?: t('Untitled'),
            'status' => $data['status'],
        ]);
        if (! $post instanceof Post) {
            return $response->notify(t('Failed to create the item.'), Notice::Error);
        }

        return $response
            ->notify(t('%s created', $type->labelName), Notice::Success)
            ->redirect(url('dashboard/edit?table=' . $type->key), 600);
    }
}
