<?php

declare(strict_types=1);

namespace app;

use app\Query\Query;
use Exception;
use Expansa\Error;
use Expansa\Image;
use Expansa\Disk;
use Expansa\Patterns;
use Expansa\Safe;

/**
 * Expansa administration Media API.
 */
final class Media
{
    /**
     * Get media files.
     *
     * @param array $args
     * @return string|array
     */
    public static function get(array $args = []): string|array
    {
        $args = [ 'type' => 'media', 'page' => 1, 'per_page' => 30, ...$args ];

        return Query::apply($args, function ($posts) {
            if (! is_array($posts)) {
                return $posts;
            }

            $sizes = Patterns\Registry::get('images');
            foreach ($posts as $i => $post) {
                if (! is_array($post) || ! is_array($sizes)) {
                    continue;
                }

                $filepath = sprintf('%si/original/%s', EX_UPLOADS, $post['slug'] ?? '');
                if (file_exists($filepath)) {
                    $file      = Disk::file($filepath);
                    $iconPath  = sprintf('%sassets/images/files/%s.svg', EX_DASHBOARD, $file->type ?? 'default');
                    $posts[$i] = [
                        ...$post,
                        ...[
                            'url'          => Url::fromPath($filepath),
                            'icon'         => Url::fromPath($iconPath),
                            'height'       => $file->height,
                            'width'        => $file->width,
                            'path'         => $file->path,
                            'extension'    => $file->extension,
                            'basename'     => $file->basename,
                            'filename'     => $file->filename,
                            'mime'         => $file->mime,
                            'type'         => $file->type,
                            'size'         => $file->size,
                            'sizeKb'       => $file->sizeKb,
                            'sizeMb'       => $file->sizeMb,
                            'sizeHumanize' => $file->sizeHumanize,
                        ],
                    ];
                }

                foreach ($sizes as $key => $size) {
                    $width  = Safe::absint($size['width'] ?? 0);
                    $height = Safe::absint($size['height'] ?? 0);
                    if (! $width || ! $height) {
                        continue;
                    }

                    $filepath = sprintf('%si/%sx%s/%s', EX_UPLOADS, $width, $height, $post['slug'] ?? '');
                    if (file_exists($filepath)) {
                        $file     = Disk::file($filepath);
                        $iconPath = sprintf('%sassets/images/files/%s.svg', EX_DASHBOARD, $file->type ?? 'default');

                        $posts[$i]['sizes'][$key] = [
                            'url'          => Url::fromPath($filepath),
                            'icon'         => Url::fromPath($iconPath),
                            'width'        => $width,
                            'height'       => $height,
                            'path'         => $file->path,
                            'extension'    => $file->extension,
                            'basename'     => $file->basename,
                            'filename'     => $file->filename,
                            'mime'         => $file->mime,
                            'type'         => $file->type,
                            'size'         => $file->size,
                            'sizeKb'       => $file->sizeKb,
                            'sizeMb'       => $file->sizeMb,
                            'sizeHumanize' => $file->sizeHumanize,
                        ];
                    }
                }
            }

            return $posts;
        });
    }

    /**
     * Uploads the file transmitted in the form to the Expansa downloads folder
     * and creates an entry about the file in the database (adds the file to the library).
     * It works with elements of the global variable $_FILES, the function needs to specify
     * an array of file data and the function itself will upload the file to the Expansa
     * downloads folder. If the file type is an image, it will create smaller copies of it.
     *
     * @param array $file Array that represents a `$_FILES` upload array.
     * @return Error|int
     * @throws Exception
     */
    public static function upload(array $file): int|Error
    {
        // TODO: add checking user capabilities

        $targetDir = sprintf('%si/original/', EX_UPLOADS);

        // upload original image
        $originalFile = Files::upload($file, $targetDir, function ($file) {
            $sizes = Patterns\Registry::get('images');
            $types = [ 'image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/webp', 'image/avif', 'image/tiff', 'image/x-icon' ];

            // now make smaller copies for images
            if (in_array($file->mime, $types, true) && is_array($sizes)) {
                foreach ($sizes as $size) {
                    $width  = Safe::absint($size['width'] ?? 0);
                    $height = Safe::absint($size['height'] ?? 0);
                    if (! $width || ! $height) {
                        continue;
                    }

                    // TODO: add file storage variations
                    $filepathResize = str_replace(
                        '/i/original/',
                        sprintf('/i/%s/', implode('x', [ $width, $height ])),
                        $file->path
                    );

                    ( new Image() )->fromFile($file->path)->thumbnail($width, $height)->toFile($filepathResize, $file->mime);
                }
            }
        });

        if ($originalFile instanceof Error) {
            return $originalFile;
        }

        return Post::add(
            'media',
            [
                'status' => 'publish',
                'title'  => $originalFile->filename,
                'slug'   => $originalFile->basename,
                'fields' => [
                    'mime' => $originalFile->mime,
                ],
            ]
        );
    }

    public static function grab(string $url)
    {
    }
}
