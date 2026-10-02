<?php

declare(strict_types=1);

namespace Expansa\Support;

use DOMDocument;

/**
 * SVG sprites and sanitizing of SVG markup by a whitelist of elements and attributes.
 *
 * ```php
 * new Svg()->addSprite($imagesDir, $spriteDir);   // builds sprite.svg from the directory
 * Svg::sprite('logo');                            // prints a symbol of the sprite
 * ```
 *
 * @package Expansa\Support
 */
final class Svg
{
    /**
     * Symbols of the last built sprite by id: width, height and viewBox.
     *
     * @var array<string, array<string, string>>
     */
    public static array $items = [];

    /**
     * Path of the last built sprite file.
     *
     * @var string
     */
    public static string $source = '';

    /**
     * Allowed elements and their allowed attributes.
     */
    private const array WHITELIST = [
        'a'              => [
            'class',
            'clip-path',
            'clip-rule',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'id',
            'mask',
            'opacity',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
            'href',
            'xlink:href',
            'xlink:title',
        ],
        'circle'         => [
            'class',
            'clip-path',
            'clip-rule',
            'cx',
            'cy',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'id',
            'mask',
            'opacity',
            'r',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
        ],
        'clipPath'       => [
            'class',
            'clipPathUnits',
            'id',
        ],
        'defs'           => [],
        'style'          => [
            'type',
        ],
        'desc'           => [],
        'ellipse'        => [
            'class',
            'clip-path',
            'clip-rule',
            'cx',
            'cy',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'id',
            'mask',
            'opacity',
            'requiredFeatures',
            'rx',
            'ry',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
        ],
        'feFlood'        => [
            'flood-opacity',
            'result',
        ],
        'feBlend'        => [
            'in',
            'in2',
            'result',
        ],
        'feGaussianBlur' => [
            'class',
            'color-interpolation-filters',
            'id',
            'requiredFeatures',
            'stdDeviation',
        ],
        'filter'         => [
            'class',
            'color-interpolation-filters',
            'filterRes',
            'filterUnits',
            'height',
            'id',
            'primitiveUnits',
            'requiredFeatures',
            'width',
            'x',
            'xlink:href',
            'y',
        ],
        'foreignObject'  => [
            'class',
            'font-size',
            'height',
            'id',
            'opacity',
            'requiredFeatures',
            'style',
            'transform',
            'width',
            'x',
            'y',
        ],
        'g'              => [
            'class',
            'clip-path',
            'clip-rule',
            'id',
            'display',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'mask',
            'opacity',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
            'font-family',
            'font-size',
            'font-style',
            'font-weight',
            'text-anchor',
        ],
        'image'          => [
            'class',
            'clip-path',
            'clip-rule',
            'filter',
            'height',
            'id',
            'mask',
            'opacity',
            'requiredFeatures',
            'style',
            'systemLanguage',
            'transform',
            'width',
            'x',
            'xlink:href',
            'xlink:title',
            'y',
        ],
        'line'           => [
            'class',
            'clip-path',
            'clip-rule',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'id',
            'marker-end',
            'marker-mid',
            'marker-start',
            'mask',
            'opacity',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
            'x1',
            'x2',
            'y1',
            'y2',
        ],
        'linearGradient' => [
            'class',
            'id',
            'gradientTransform',
            'gradientUnits',
            'requiredFeatures',
            'spreadMethod',
            'systemLanguage',
            'x1',
            'x2',
            'xlink:href',
            'y1',
            'y2',
        ],
        'marker'         => [
            'id',
            'class',
            'markerHeight',
            'markerUnits',
            'markerWidth',
            'orient',
            'preserveAspectRatio',
            'refX',
            'refY',
            'systemLanguage',
            'viewBox',
        ],
        'mask'           => [
            'class',
            'height',
            'id',
            'maskContentUnits',
            'maskUnits',
            'width',
            'x',
            'y',
        ],
        'metadata'       => [
            'class',
            'id',
        ],
        'path'           => [
            'class',
            'clip-path',
            'clip-rule',
            'd',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'id',
            'marker-end',
            'marker-mid',
            'marker-start',
            'mask',
            'opacity',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
        ],
        'pattern'        => [
            'class',
            'height',
            'id',
            'patternContentUnits',
            'patternTransform',
            'patternUnits',
            'requiredFeatures',
            'style',
            'systemLanguage',
            'viewBox',
            'width',
            'x',
            'xlink:href',
            'y',
        ],
        'polygon'        => [
            'class',
            'clip-path',
            'clip-rule',
            'id',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'id',
            'class',
            'marker-end',
            'marker-mid',
            'marker-start',
            'mask',
            'opacity',
            'points',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
        ],
        'polyline'       => [
            'class',
            'clip-path',
            'clip-rule',
            'id',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'marker-end',
            'marker-mid',
            'marker-start',
            'mask',
            'opacity',
            'points',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
        ],
        'radialGradient' => [
            'class',
            'cx',
            'cy',
            'fx',
            'fy',
            'gradientTransform',
            'gradientUnits',
            'id',
            'r',
            'requiredFeatures',
            'spreadMethod',
            'systemLanguage',
            'xlink:href',
        ],
        'rect'           => [
            'class',
            'clip-path',
            'clip-rule',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'height',
            'id',
            'mask',
            'opacity',
            'requiredFeatures',
            'rx',
            'ry',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
            'width',
            'x',
            'y',
        ],
        'stop'           => [
            'class',
            'id',
            'offset',
            'requiredFeatures',
            'stop-color',
            'stop-opacity',
            'style',
            'systemLanguage',
        ],
        'svg'            => [
            'class',
            'clip-path',
            'clip-rule',
            'filter',
            'fill',
            'fill-rule',
            'id',
            'height',
            'mask',
            'preserveAspectRatio',
            'requiredFeatures',
            'style',
            'systemLanguage',
            'viewBox',
            'width',
            'x',
            'xmlns',
            'xmlns:se',
            'xmlns:xlink',
            'y',
        ],
        'switch'         => [
            'class',
            'id',
            'requiredFeatures',
            'systemLanguage',
        ],
        'symbol'         => [
            'class',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'font-family',
            'font-size',
            'font-style',
            'font-weight',
            'id',
            'opacity',
            'preserveAspectRatio',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'transform',
            'viewBox',
        ],
        'text'           => [
            'class',
            'clip-path',
            'clip-rule',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'font-family',
            'font-size',
            'font-style',
            'font-weight',
            'id',
            'mask',
            'opacity',
            'requiredFeatures',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'text-anchor',
            'transform',
            'x',
            'xml:space',
            'y',
        ],
        'textPath'       => [
            'class',
            'id',
            'method',
            'requiredFeatures',
            'spacing',
            'startOffset',
            'style',
            'systemLanguage',
            'transform',
            'xlink:href',
        ],
        'title'          => [],
        'tspan'          => [
            'class',
            'clip-path',
            'clip-rule',
            'dx',
            'dy',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'font-family',
            'font-size',
            'font-style',
            'font-weight',
            'id',
            'mask',
            'opacity',
            'requiredFeatures',
            'rotate',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'systemLanguage',
            'text-anchor',
            'textLength',
            'transform',
            'x',
            'xml:space',
            'y',
        ],
        'use'            => [
            'class',
            'clip-path',
            'clip-rule',
            'fill',
            'fill-opacity',
            'fill-rule',
            'filter',
            'height',
            'id',
            'mask',
            'stroke',
            'stroke-dasharray',
            'stroke-dashoffset',
            'stroke-linecap',
            'stroke-linejoin',
            'stroke-miterlimit',
            'stroke-opacity',
            'stroke-width',
            'style',
            'transform',
            'width',
            'x',
            'xlink:href',
            'y',
        ],
    ];

    private DOMDocument $xml;

    public function __construct()
    {
        $this->xml                     = new DOMDocument();
        $this->xml->preserveWhiteSpace = false;
        $this->xml->formatOutput       = true;
    }

    /**
     * Find the .svg files of a directory and its subdirectories.
     *
     * @param string $path Directory with a trailing slash.
     * @return string[]
     */
    public function globTreeFiles(string $path): array
    {
        $out = [];
        foreach (glob($path . '*.svg') as $file) {
            if (is_dir($file)) {
                $out = array_merge($out, $this->globTreeFiles($file));
            } else {
                $out[] = $file;
            }
        }
        return $out;
    }

    /**
     * Render a symbol of the sprite as an `<svg><use></svg>` element.
     *
     * @param string $id    Symbol id: the file name of the source SVG.
     * @param bool   $print Print the markup as well as return it.
     * @return string Empty for an unknown symbol.
     */
    public static function sprite(string $id, bool $print = true): string
    {
        $id     = trim($id);
        $symbol = self::$items[$id] ?? [];

        if ($symbol === [] || $id === '') {
            return '';
        }

        $url = Url::toUrl(self::$source) . "#{$id}";

        ob_start();
        ?>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none"<?php echo Arr::toHtmlAttributes($symbol); ?>>
            <use xlink:href="/<?php echo $url; ?>"></use>
        </svg>
        <?php
        if ($print) {
            return ob_get_flush();
        }

        return ob_get_clean();
    }

    /**
     * Build `sprite.svg` from the .svg files of a directory and remember their symbols.
     *
     * @param string $fromDir Directory of the source files, with a trailing slash.
     * @param string $toDir   Directory of the sprite, with a trailing slash.
     * @return void
     */
    public function addSprite(string $fromDir, string $toDir): void
    {
        $sprite = $toDir . 'sprite.svg';
        $files  = array_filter(glob($fromDir . '*.svg') ?: [], 'file_exists');
        if ($files) {
            $symbols = [];
            foreach ($files as $file) {
                if ($this->load($file)) {
                    $elements        = $this->xml->getElementsByTagName('*');
                    $symbolWhitelist = self::WHITELIST['symbol'];
                    $node            = $elements->item(0);

                    if ($node->tagName === 'svg') {
                        // add required attributes
                        $filename = trim(pathinfo($file, PATHINFO_FILENAME));
                        if ($filename) {
                            $dom        = $this->xml->createAttribute('id');
                            $dom->value = $filename;
                            $this->xml->documentElement->appendChild($dom);
                        }

                        if (! $node->hasAttribute('viewBox')) {
                            $width  = $this->xml->documentElement->getAttribute('width');
                            $height = $this->xml->documentElement->getAttribute('height');
                            if ($width && $height) {
                                $dom        = $this->xml->createAttribute('viewBox');
                                $dom->value = sprintf('0 0 %d %d', $width, $height);
                                $this->xml->documentElement->appendChild($dom);

                                self::$items[$filename] = [
                                    'width'   => $width,
                                    'height'  => $height,
                                    'viewBox' => $this->xml->documentElement->getAttribute('viewBox'),
                                ];
                            }
                        }

                        // remove all not allowed attributes
                        for ($x = 0; $x < $node->attributes->length; ++$x) {
                            $attr = $node->attributes->item($x)->name;
                            if (! in_array($attr, $symbolWhitelist, true)) {
                                $node->removeAttribute($attr);
                                --$x;
                            }
                        }

                        $symbols[] = str_replace(['<svg', 'svg>'], ['<symbol', 'symbol>'], $this->xml->saveHTML());
                    }
                }
            }

            self::$source = $sprite;

            file_put_contents(
                $sprite,
                sprintf(
                    '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">%s</svg>',
                    implode('', $symbols)
                )
            );
        }
    }

    /**
     * Load an SVG file.
     *
     * @param string $file
     * @return bool
     */
    public function load(string $file): bool
    {
        return $this->xml->load($file);
    }

    /**
     * Load SVG markup.
     *
     * @param string $source
     * @return void
     */
    public function loadXML(string $source): void
    {
        $this->xml->loadXML($source);
    }

    /**
     * Save the loaded SVG to a file.
     *
     * @param string $file
     * @return int|false Bytes written.
     */
    public function save(string $file): int|false
    {
        return $this->xml->save($file);
    }

    /**
     * Get the markup of the loaded SVG.
     *
     * @return string|false
     */
    public function saveXML(): string|false
    {
        return $this->xml->saveXML($this->xml->documentElement);
    }

    /**
     * Remove the elements and attributes that are not whitelisted from the loaded SVG.
     *
     * @see https://github.com/alnorris/SVG-Sanitizer
     * @return void
     */
    public function sanitize(): void
    {
        $elements = $this->xml->getElementsByTagName('*');

        for ($i = 0; $i < $elements->length; ++$i) {
            $node = $elements->item($i);

            $allowed = self::WHITELIST[$node->tagName] ?? null;

            if ($allowed !== null) {
                for ($x = 0; $x < $node->attributes->length; ++$x) {
                    $attr = $node->attributes->item($x)->name;

                    if (! in_array($attr, $allowed, true)) {
                        $node->removeAttribute($attr);
                        --$x;
                    }
                }
            } else {
                $node->parentNode->removeChild($node);
                --$i;
            }
        }
    }
}
