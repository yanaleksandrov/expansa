<?php

declare(strict_types=1);

namespace App\Api\Post;

use App\Models\Post;
use Expansa\Facades\Safe;
use Expansa\Http\Request;

final class PostService
{
    public function create(Request $request): array
    {
        $data = Safe::data($request->post, [
            'post-type' => 'text',
            'title'     => 'text',
            'status'    => 'text',
        ])->apply();

        [$type, $args] = [array_shift($data), $data];

        if (!$type) {
            return ['method' => t('Post type is missing.')];
        }

        Post::add($type, $args);

        return ['method' => 'POST create user'];
    }
}
