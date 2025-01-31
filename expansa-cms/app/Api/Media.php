<?php

namespace App\Api;

use Expansa\Error;
use Expansa\Facades\Disk;
use Expansa\Support\Str;

class Media
{
    /**
     * Endpoint name.
     */
    public string $endpoint = 'media';

    /**
     * Get media files.
     *
     * @since 2025.1
     */
    public static function get(): array
    {
        $media = \App\Media::get(
            [
                'per_page' => 60,
            ]
        );

        return [
            'posts' => $media,
        ];
    }

    /**
     * Upload new file to media.
     *
     * @since 2025.1
     */
    public static function upload(): array
    {
        $errors = [];
        $posts  = [];
        foreach ($_FILES as $file) {
            $filename = $file['name'] ?? '';
            $postID   = \App\Media::upload($file);
            if ($postID instanceof Error) {
                $errors[ $filename ] = Error::get();
            } else {
                $posts[] = \App\Post::get('media', $postID);
            }
        }

        return [
            'notice'   => empty($errors) ? t('%d files have been successfully uploaded to the library', count($posts)) : '',
            'uploaded' => count($posts) > 0,
            'posts'    => $posts,
            'errors'   => $errors,
        ];
    }

    /**
     * Upload files from external url.
     *
     * @since 2025.1
     */
    public static function grab(): array
    {
        $errors = [];
        $files  = [];
        $urls   = Str::extractUrls($_POST['urls'] ?? '');
        echo '<pre>';
        if ($urls) {
            $filepath = sprintf('%si/original/', EX_STORAGE);

            foreach ($urls as $url) {
                $files[$url] = Disk::file($filepath)->grab($url);
            }
        }
        print_r($files);

        return [
            'notice'   => empty($errors) ? t('%d files have been successfully uploaded to the library', count($files)) : '',
            'uploaded' => count($files) > 0,
            'files'    => $files,
            'errors'   => $errors,
        ];
    }
}
