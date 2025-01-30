<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;
use Spatie\Image\Image as SpatieImage;

/**
 * Image facade for interacting with the image processing functionality.
 * This facade provides static methods for image operations like loading,
 * creating a new image, and more.
 *
 * @method static SpatieImage load(string $pathToImage) Load an image from a file.
 * @method static SpatieImage new(int $width, int $height, ?string $backgroundColor = null)
 * Create a new image with the specified dimensions and optional background color.
 */
class Image extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Images\Manager';
    }
}
