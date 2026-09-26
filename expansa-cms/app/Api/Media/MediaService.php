<?php

declare(strict_types=1);

namespace App\Api\Media;

use App\Models\Media;
use App\Models\Post;
use Expansa\Debug\Error;
use Expansa\Support\Str;

final class MediaService
{
    public function list(array $args = []): array
    {
        return [
            'posts' => Media::get([ ...$args, 'per_page' => 60 ]),
        ];
    }

    public function upload(array $files): array
    {
        $errors = [];
        $posts  = [];

        foreach ($files as $file) {
            $filename = $file['name'] ?? '';
            $postId   = Media::upload($file);

            if ($postId instanceof Error) {
                $errors[$filename] = $postId;
            } else {
                $posts[] = Post::get('files', $postId);
            }
        }

        return [
            'notice'   => empty($errors) ? t('%d files have been successfully uploaded to the library', count($posts)) : '',
            'uploaded' => count($posts) > 0,
            'posts'    => $posts,
            'errors'   => $errors,
        ];
    }

    public function grab(string $urls): array
    {
        $errors = [];
        $posts  = [];
        $urls   = Str::extractUrls($urls);

        foreach ($urls as $url) {
            $postId = Media::grab($url);

            if ($postId instanceof Error) {
                $errors[$url] = $postId;
            } else {
                $posts[] = Post::get('files', $postId);
            }
        }

        return [
            'notice'   => empty($errors) ? t('%d files have been successfully uploaded to the library', count($posts)) : '',
            'uploaded' => count($posts) > 0,
            'posts'    => $posts,
            'errors'   => $errors,
        ];
    }

    public function delete(array $ids): array
    {
        $deleted = [];

        foreach (array_unique(array_map('intval', $ids)) as $id) {
            if ($id > 0 && Media::delete($id)) {
                $deleted[] = $id;
            }
        }

        return [
            'notice'  => $deleted ? t('%d files have been deleted from the library', count($deleted)) : '',
            'deleted' => $deleted,
        ];
    }
}
