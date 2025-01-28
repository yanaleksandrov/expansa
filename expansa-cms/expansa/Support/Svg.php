<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * Usage:
 * $svg = new Svg();
 * $svg->addSprite( EX_DASHBOARD . 'assets/images/', EX_DASHBOARD . 'assets/' ); // create sprite.
 *
 * TODO: 1. use "link preload" & inline svg output
 *
 * Svg::sprite( 'logo' ); // output symbol
 */
class Svg
{
    public static array $items = [];

    public static string $source = '';

    private DOMDocument $xml;

    /**
     * Defines the whitelist of elements and attributes allowed.
     */
    private static array $whitelist = [
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

    public function __construct()
    {
        $this->xml = new DOMDocument();
        $this->xml->preserveWhiteSpace = false;
        $this->xml->formatOutput = true;
    }

    public function globTreeFiles($path): array
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

    public static function sprite($id, bool $print = true): bool|string
    {
        $id = trim($id ?? '');
        $symbol = (array) (self::$items[$id] ?? []);

        if (empty($symbol) || empty($id)) {
            return '';
        }

        $url = str_replace(EX_PATH, url(), self::$source) . "#{$id}";

        ob_start();
        ?>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none"<?php echo Arr::toHtmlAtts($symbol); ?>>
            <use xlink:href="/<?php echo $url; ?>"></use>
        </svg>
        <?php
        if ($print) {
            return ob_get_flush();
        }

        return ob_get_clean();
    }

    public function addSprite($from_dir, $to_dir): string
    {
        $sprite = $to_dir . 'sprite.svg';
        $files = array_filter(glob($from_dir . '*.svg'), 'file_exists');
        if (! empty($files)) {
            $_files = [];
            foreach ($files as $file) {
                $is_loaded = $this->load($file);
                if ($is_loaded) {
                    $elements = $this->xml->getElementsByTagName('*');
                    $symbol_whitelist = self::$whitelist['symbol'];
                    $node = $elements->item(0);

                    if ($node->tagName === 'svg') {
                        // add required attributes
                        $filename = trim(pathinfo($file, PATHINFO_FILENAME));
                        if ($filename) {
                            $dom = $this->xml->createAttribute('id');
                            $dom->value = $filename;
                            $this->xml->documentElement->appendChild($dom);
                        }

                        if (! $node->hasAttribute('viewBox')) {
                            $width = $this->xml->documentElement->getAttribute('width');
                            $height = $this->xml->documentElement->getAttribute('height');
                            if ($width && $height) {
                                $dom = $this->xml->createAttribute('viewBox');
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
                            if (! in_array($attr, $symbol_whitelist, true)) {
                                $node->removeAttribute($attr);
                                --$x;
                            }
                        }

                        $_files[] = str_replace(['<svg', 'svg>'], ['<symbol', 'symbol>'], $this->xml->saveHTML());
                    }
                }
            }

            self::$source = $sprite;

            file_put_contents(
                $sprite,
                sprintf(
                    '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">%s</svg>',
                    implode(
                        '',
                        $_files
                    )
                )
            );
        }
        return '';
    }

    // load XML SVG
    public function load($file)
    {
        return $this->xml->load($file);
    }

    // load XML SVG
    public function loadXML($source): void
    {
        $this->xml->loadXML($source);
    }

    public function save($file)
    {
        return $this->xml->save($file);
    }

    public function saveXML()
    {
        return $this->xml->saveXML($this->xml->documentElement);
    }

    /**
     * @see https://github.com/alnorris/SVG-Sanitizer
     */
    public function sanitize(): void
    {
        // all elements in xml doc
        $elements = $this->xml->getElementsByTagName('*');

        // loop through all elements
        for ($i = 0; $i < $elements->length; ++$i) {
            $node = $elements->item($i);

            // array of allowed attributes in specific element
            $whitelist_attr_arr = self::$whitelist[$node->tagName];

            // does element exist in whitelist?
            if (isset($whitelist_attr_arr)) {
                for ($x = 0; $x < $node->attributes->length; ++$x) {
                    // get attributes name
                    $attr = $node->attributes->item($x)->name;

                    // check if attribute isn't in whitelist
                    if (! in_array($attr, $whitelist_attr_arr, true)) {
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
