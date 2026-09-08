<?php

declare(strict_types=1);

namespace App\Api\Media;

use App\Models\Media;
use App\Models\Post;
use Expansa\Debug\Error;
use Expansa\Facades\Disk;
use Expansa\Support\Str;

final class MediaService
{
    public function list(): array
    {
        return [
            'posts' => Media::get(['per_page' => 60]),
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
                $errors[$filename] = Error::get();
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
        $files  = [];
        $urls   = Str::extractUrls($urls);

        if ($urls) {
            $filepath = sprintf('%si/original/', EX_STORAGE);

            foreach ($urls as $url) {
                $files[$url] = Disk::file($filepath)->grab($url);
            }
        }

        return [
            'notice'   => empty($errors) ? t('%d files have been successfully uploaded to the library', count($files)) : '',
            'uploaded' => count($files) > 0,
            'files'    => $files,
            'errors'   => $errors,
        ];
    }
}
