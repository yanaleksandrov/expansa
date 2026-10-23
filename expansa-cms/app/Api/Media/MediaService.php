<?php

declare(strict_types=1);

namespace App\Api\Media;

use App\Models\Media;
use Expansa\Http\Request;
use Expansa\Support\Error;
use Expansa\Support\Str;

final class MediaService
{
    public function list(Request $request): array
    {
        return [
            'posts' => Media::get(max(1, $request->getInt('page', 1)), $request->getString('s'), 60),
        ];
    }

    public function upload(Request $request): array
    {
        $errors = [];
        $posts  = [];

        foreach ($request->files as $file) {
            $filename = $file['name'] ?? '';
            $postId   = Media::upload($file);

            if ($postId instanceof Error) {
                $errors[$filename] = $postId;
            } else {
                $posts[] = Media::find($postId);
            }
        }

        return [
            'notice'   => empty($errors) ? t('%d files have been successfully uploaded to the library.', count($posts)) : '',
            'uploaded' => count($posts) > 0,
            'posts'    => array_values(array_filter($posts)),
            'errors'   => $errors,
        ];
    }

    public function grab(Request $request): array
    {
        $errors = [];
        $posts  = [];
        $urls   = Str::extractUrls((string) ($request->post['urls'] ?? ''));

        foreach ($urls as $url) {
            $postId = Media::grab($url);

            if ($postId instanceof Error) {
                $errors[$url] = $postId;
            } else {
                $posts[] = Media::find($postId);
            }
        }

        return [
            'notice'   => empty($errors) ? t('%d files have been successfully uploaded to the library.', count($posts)) : '',
            'uploaded' => count($posts) > 0,
            'posts'    => array_values(array_filter($posts)),
            'errors'   => $errors,
        ];
    }

    public function delete(Request $request): array
    {
        $deleted = [];

        foreach (array_unique(array_map('intval', (array) ($request->post['ids'] ?? []))) as $id) {
            if ($id > 0 && Media::delete($id)) {
                $deleted[] = $id;
            }
        }

        return [
            'notice'  => $deleted ? t('%d files have been deleted from the library.', count($deleted)) : '',
            'deleted' => $deleted,
        ];
    }
}
