<?php

declare(strict_types=1);

namespace Expansa\Assets\Providers;

use Expansa\Assets\Abstracts\Provider;

class Link extends Provider
{
    /**
     * Constructor for the link asset, defining various attributes of the link tag.
     *
     * @param string $uid            Unique ID attribute of the asset.
     * @param string $href           URL or path to the linked resource.
     * @param array  $data           Additional data for the asset.
     * @param string $class          CSS class name for the link tag.
     * @param string $media          Media attribute specifying the media type for the asset.
     * @param string $rel            Relationship between the current document and the linked resource.
     * @param string $sizes          Sizes of the icons for visual media, used with rel="icon".
     * @param string $type           MIME type of the linked resource (default is 'css').
     * @param string $hreflang       Language of the linked resource.
     * @param string $as             Type of resource being linked (e.g., script, style, font).
     * @param string $crossorigin    Indicates how the resource should be fetched across origins.
     * @param string $integrity      Security feature providing a hash-based integrity check.
     * @param string $title          Alternative title for the resource.
     * @param string $disabled       Disables the link if present (primarily for stylesheets).
     * @param string $referrerpolicy Specifies the referrer information to be included with the request.
     * @param string $path           Computed path to the linked resource.
     */
    public function __construct(
        public string $uid,
        public string $href,
        public array $data = [],
        public string $class = '',
        public string $media = '',
        public string $rel = 'stylesheet',
        public string $type = 'text/css',
        public string $sizes = '',
        public string $hreflang = '',
        public string $as = '',
        public string $crossorigin = '',
        public string $integrity = '',
        public string $title = '',
        public string $disabled = '',
        public string $referrerpolicy = '',
        public string $path = '',
        public string $id = '',
        public string $version = '',
    )
    {
        $this->path = $this->toPath($href);
        $this->id   = sprintf('%s-%s', $this->uid, pathinfo($this->path, PATHINFO_EXTENSION));

        foreach ($data as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * Get html tag of resource.
     *
     * @param Provider $asset
     * @return string
     */
    public function render(Provider $asset): string
    {
        $attributes = array_diff_key((array) $asset, array_flip(['uid', 'path', 'data']));

        return sprintf("	<link%s/>\n", $this->sanitizeAttributes($attributes));
    }

    /**
     * CSS minifier
     *
     * @param string $code
     * @return string
     */
    public function minify(string $code): string
    {
        // remove comments
        $code = preg_replace('/\/\*[\s\S]*?\*\//', '', $code, -1);
        $code = preg_replace('/\/\/.*$/m', '', $code);

        // remove unnecessary spaces and line breaks
        $code = preg_replace('/\s+/', ' ', $code);
        $code = str_replace(["\r\n", "\r", "\n", "\t"], '', $code);

        // remove unnecessary spaces before symbols
        return preg_replace('/\s*([=:;,+\-*\/<>\(\)\{\}\[\]])\s*/', '$1', $code);
    }
}
