<?php

declare(strict_types=1);

namespace Expansa\Images;

use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\ColorFormat;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Enums\FlipDirection;
use Spatie\Image\Enums\Orientation;
use Spatie\Image\Image;
use Spatie\Image\Exceptions\InvalidImageDriver;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Avifenc;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;

/**
 * Image editing over Spatie Image, the Image facade instance: `Image::load($path)->crop(300, 200)->save($to)`.
 * One image at a time: load() or new() replaces the current one.
 *
 * @package Expansa\Images
 */
final class Manager
{
    /**
     * Spatie image with the Imagick driver, GD without the imagick extension.
     */
    public readonly Image $driver;

    /**
     * @throws InvalidImageDriver If neither Imagick nor GD is available.
     */
    public function __construct()
    {
        $this->driver = Image::useImageDriver(extension_loaded('imagick') ? ImageDriver::Imagick : ImageDriver::Gd);
    }

    public function load(string $pathToImage): static
    {
        $this->driver->loadFile($pathToImage);

        return $this;
    }

    public function new(int $width, int $height, ?string $backgroundColor = null): static
    {
        $this->driver->new($width, $height, $backgroundColor);

        return $this;
    }

    public function save(string $path = ''): static
    {
        $this->driver->save($path);

        return $this;
    }

    public function getWidth(): int
    {
        return $this->driver->getWidth();
    }

    public function getHeight(): int
    {
        return $this->driver->getHeight();
    }

    public function brightness(int $brightness): static
    {
        $this->driver->brightness($brightness);

        return $this;
    }

    public function gamma(float $gamma): static
    {
        $this->driver->gamma($gamma);

        return $this;
    }

    public function contrast(float $level): static
    {
        $this->driver->contrast($level);

        return $this;
    }

    public function blur(int $blur): static
    {
        $this->driver->blur($blur);

        return $this;
    }

    public function colorize(int $red, int $green, int $blue): static
    {
        // This construction puts the value in the range from -100 to 100 if it goes beyond it.
        $red   = max(-100, min(100, $red));
        $green = max(-100, min(100, $green));
        $blue  = max(-100, min(100, $blue));

        $this->driver->colorize($red, $green, $blue);

        return $this;
    }

    public function greyscale(): static
    {
        $this->driver->greyscale();

        return $this;
    }

    public function sepia(): static
    {
        $this->driver->sepia();

        return $this;
    }

    public function sharpen(float $amount): static
    {
        $amount = max(0, min(100, $amount));

        $this->driver->sharpen($amount);

        return $this;
    }

    public function fit(
        Fit $fit,
        ?int $desiredWidth = null,
        ?int $desiredHeight = null,
        bool $relative = false,
        string $backgroundColor = '#ffffff'
    ): static
    {
        $this->driver->fit($fit, $desiredWidth, $desiredHeight, $relative, $backgroundColor);

        return $this;
    }

    public function pickRgbaColor(int $x, int $y): mixed
    {
        return $this->driver->pickColor($x, $y, ColorFormat::Rgba);
    }

    public function pickHexColor(int $x, int $y): mixed
    {
        return $this->driver->pickColor($x, $y, ColorFormat::Hex);
    }

    public function pickIntColor(int $x, int $y): mixed
    {
        return $this->driver->pickColor($x, $y, ColorFormat::Int);
    }

    public function pickArrayColor(int $x, int $y): mixed
    {
        return $this->driver->pickColor($x, $y, ColorFormat::Array);
    }

    public function pickObjectColor(int $x, int $y): mixed
    {
        return $this->driver->pickColor($x, $y, ColorFormat::Object);
    }

    public function manualCrop(int $width, int $height, ?int $x = null, ?int $y = null): static
    {
        $this->driver->manualCrop($width, $height, $x, $y);

        return $this;
    }

    public function crop(int $width, int $height, CropPosition $position = CropPosition::Center): static
    {
        $this->driver->crop($width, $height, $position);

        return $this;
    }

    public function focalCrop(int $width, int $height, ?int $cropCenterX = null, ?int $cropCenterY = null): static
    {
        $this->driver->focalCrop($width, $height, $cropCenterX, $cropCenterY);

        return $this;
    }

    public function base64(string $imageFormat = 'jpeg', bool $prefixWithFormat = true): string
    {
        return $this->driver->base64($imageFormat, $prefixWithFormat);
    }

    public function background(string $color): static
    {
        $this->driver->background($color);

        return $this;
    }

    public function rotate(Orientation $orientation = Orientation::Rotate180): static
    {
        $this->driver->orientation($orientation);

        return $this;
    }

    public function exif(): array
    {
        return $this->driver->exif();
    }

    public function flipHorizontal(): static
    {
        $this->driver->flip(FlipDirection::Horizontal);

        return $this;
    }

    public function flipVertical(): static
    {
        $this->driver->flip(FlipDirection::Vertical);

        return $this;
    }

    public function flipBoth(): static
    {
        $this->driver->flip(FlipDirection::Both);

        return $this;
    }

    public function pixelate(int $pixelate = 50): static
    {
        $pixelate = max(0, min(100, $pixelate));

        $this->driver->pixelate($pixelate);

        return $this;
    }

    public function image(): mixed
    {
        return $this->driver->image();
    }

    public function resize(int $width, int $height, array $constraints = []): static
    {
        $this->driver->resize($width, $height, $constraints);

        return $this;
    }

    public function width(int $width): static
    {
        $this->driver->width($width);

        return $this;
    }

    public function height(int $height): static
    {
        $this->driver->height($height);

        return $this;
    }

    public function quality(int $quality): static
    {
        $this->driver->quality($quality);

        return $this;
    }

    public function format(string $format): static
    {
        $this->driver->format($format);

        return $this;
    }

    /**
     * Optimize the saved file with the tool matching its type: jpegoptim, pngquant, svgo, gifsicle, cwebp or avifenc.
     * The tool is picked by save() from the file mime type, missing tools are skipped.
     *
     * @param int $quality From 0 to 100.
     * @return static
     */
    public function optimize(int $quality = 90): static
    {
        $quality = max(0, min(100, $quality));

        $this->driver->optimize(
            new OptimizerChain()
                ->addOptimizer(new Jpegoptim(["-m$quality", '--force', '--strip-all', '--all-progressive']))
                ->addOptimizer(new Pngquant(["--quality=$quality", '--force']))
                ->addOptimizer(new Svgo())
                ->addOptimizer(new Gifsicle(['-b', '-O3']))
                ->addOptimizer(new Cwebp(['-m 6', '-pass 10', '-mt', "-q $quality"]))
                ->addOptimizer(new Avifenc([
                    '-a cq-level=' . round(63 - $quality * 0.63),
                    '-j all',
                    '--min 0',
                    '--max 63',
                    '--minalpha 0',
                    '--maxalpha 63',
                    '-a end-usage=q',
                    '-a tune=ssim',
                ]))
                ->setTimeout(60)
        );

        return $this;
    }
}
