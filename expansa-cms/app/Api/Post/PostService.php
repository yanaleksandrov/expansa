<?php

declare(strict_types=1);

namespace App\Api\Post;

use App\Models\Post;
use Expansa\Facades\Safe;

final class PostService
{
    public function create(array $input): array
    {
        $data = Safe::data($input, [
            'post-type' => 'text',
            'title'     => 'text',
            'status'    => 'text',
        ])->apply();

        [$type, $args] = [array_shift($data), $data];

        if (!$type) {
            return ['method' => t('Post type is missing')];
        }

        Post::add($type, $args);

        return ['method' => 'POST create user'];
    }
}
