<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Images\Manager;
use Expansa\Patterns\Facade;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Orientation;

/**
 * Image facade: `Image::load($path)->crop(300, 200)->save($to)`.
 *
 * @method static Manager load(string $pathToImage)
 * @method static Manager new(int $width, int $height, ?string $backgroundColor = null)
 * @method static Manager save(string $path = '')
 * @method static int     getWidth()
 * @method static int     getHeight()
 * @method static Manager brightness(int $brightness)
 * @method static Manager gamma(float $gamma)
 * @method static Manager contrast(float $level)
 * @method static Manager blur(int $blur)
 * @method static Manager colorize(int $red, int $green, int $blue)
 * @method static Manager greyscale()
 * @method static Manager sepia()
 * @method static Manager sharpen(float $amount)
 * @method static Manager fit(Fit $fit, ?int $desiredWidth = null, ?int $desiredHeight = null, bool $relative = false, string $backgroundColor = '#ffffff')
 * @method static mixed   pickRgbaColor(int $x, int $y)
 * @method static mixed   pickHexColor(int $x, int $y)
 * @method static mixed   pickIntColor(int $x, int $y)
 * @method static mixed   pickArrayColor(int $x, int $y)
 * @method static mixed   pickObjectColor(int $x, int $y)
 * @method static Manager manualCrop(int $width, int $height, ?int $x = null, ?int $y = null)
 * @method static Manager crop(int $width, int $height, CropPosition $position = CropPosition::Center)
 * @method static Manager focalCrop(int $width, int $height, ?int $cropCenterX = null, ?int $cropCenterY = null)
 * @method static string  base64(string $imageFormat = 'jpeg', bool $prefixWithFormat = true)
 * @method static Manager background(string $color)
 * @method static Manager rotate(Orientation $orientation = Orientation::Rotate180)
 * @method static array   exif()
 * @method static Manager flipHorizontal()
 * @method static Manager flipVertical()
 * @method static Manager flipBoth()
 * @method static Manager pixelate(int $pixelate = 50)
 * @method static mixed   image()
 * @method static Manager resize(int $width, int $height, array $constraints = [])
 * @method static Manager width(int $width)
 * @method static Manager height(int $height)
 * @method static Manager quality(int $quality)
 * @method static Manager format(string $format)
 * @method static Manager optimize(int $quality = 90)
 */
class Image extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Images\Manager::class;
    }
}
