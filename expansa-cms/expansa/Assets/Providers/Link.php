<?php

declare(strict_types=1);

namespace Expansa\Assets\Providers;

use Expansa\Assets\Abstracts\Provider;

class Link extends Provider
{
    /**
     * Computes `path` from `href`; `data` entries override matching properties except `id`.
     */
    public function __construct(

        /**
         * Unique ID attribute of the asset.
         */
        public string $uid,

        /**
         * URL or path to the linked resource.
         */
        public string $href,

        /**
         * Additional data for the asset.
         */
        public array $data = [],

        /**
         * CSS class name for the link tag.
         */
        public string $class = '',

        /**
         * Media attribute specifying the media type for the asset.
         */
        public string $media = '',

        /**
         * Relationship between the current document and the linked resource.
         */
        public string $rel = 'stylesheet',

        /**
         * MIME type of the linked resource.
         */
        public string $type = 'text/css',

        /**
         * Sizes of the icons for visual media, used with rel="icon".
         */
        public string $sizes = '',

        /**
         * Language of the linked resource.
         */
        public string $hreflang = '',

        /**
         * Type of resource being linked (e.g., script, style, font).
         */
        public string $as = '',

        /**
         * Indicates how the resource should be fetched across origins.
         */
        public string $crossorigin = '',

        /**
         * Security feature providing a hash-based integrity check.
         */
        public string $integrity = '',

        /**
         * Alternative title for the resource.
         */
        public string $title = '',

        /**
         * Disables the link if present (primarily for stylesheets).
         */
        public string $disabled = '',

        /**
         * Specifies the referrer information to be included with the request.
         */
        public string $referrerpolicy = '',

        /**
         * Computed path to the linked resource.
         */
        public string $path = '',

        /**
         * Asset version; not added to the URL (cached copies use content-hashed names) and ignored when checking if the asset can be bundled.
         */
        public string $version = '',

        /**
         * uid's (or full ids) of assets that must be output before this one.
         */
        public array $dependencies = [],

        /**
         * Output before close body tag.
         */
        public bool $toFooter = false,
    )
    {
        $this->path = self::toPath($href);

        foreach ($data as $name => $value) {
            if ($name !== 'id' && property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * Unique id of this asset within the manager: `{uid}-{extension}`.
     */
    public string $id {
        get => sprintf('%s-%s', $this->uid, pathinfo($this->path ?: $this->href, PATHINFO_EXTENSION));
    }

    /**
     * Get HTML tag of resource. $inline embeds the content as a `<style>` tag instead of
     * linking to it; $minify runs it through minify() first either way - but only for an
     * actual stylesheet (`rel === 'stylesheet'`, the default). `style()`/Link also gets reused
     * for non-CSS `<link>`s (favicons, manifest, preload, ...); minifying/inlining a PNG or a
     * webmanifest as if it were CSS text would corrupt it, so those always render as-is
     * regardless of the flags. No effect either on an asset with no local file to read.
     *
     * @param Provider $asset
     * @param bool     $minify
     * @param bool     $inline
     * @return string
     */
    public function render(Provider $asset, bool $minify = false, bool $inline = false): string
    {
        $isStylesheet = $asset->rel === 'stylesheet';
        $content      = ($isStylesheet && ($minify || $inline)) ? $this->readContent($asset, $minify) : null;

        if ($inline && $content !== null) {
            return $this->renderInline($asset, $content);
        }

        $attributes = array_diff_key(get_object_vars($asset), array_flip(['uid', 'path', 'data', 'dependencies', 'toFooter']));

        if ($minify && $content !== null) {
            // Null means the write failed (disk full, permissions, ...) - keep the original
            // href rather than link to a cached file that was never actually written.
            $attributes['href'] = $this->cacheMinifiedFile($asset->uid, $content, 'css') ?? $attributes['href'];
        }

        return sprintf("	<link%s/>\n", $this->sanitizeAttributes($attributes));
    }

    /**
     * Embed already-computed CSS $content directly as a `<style>` tag, keeping only the
     * attributes that still make sense without a href (id, class, media).
     */
    public function renderInline(Provider $asset, string $content): string
    {
        $attributes = array_intersect_key(get_object_vars($asset), array_flip(['id', 'class', 'media']));

        return sprintf("	<style%s>%s</style>\n", $this->sanitizeAttributes($attributes), $content);
    }

    /**
     * CSS minifier
     *
     * @param string $code
     * @return string
     */
    public function minify(string $code): string
    {
        // remove comments (CSS only has /* */ - no //, which would wrongly truncate
        // url(http://...) and url(//...) at the first slash pair)
        $code = preg_replace('/\/\*[\s\S]*?\*\//', '', $code, -1);

        // remove unnecessary spaces and line breaks
        $code = preg_replace('/\s+/', ' ', $code);
        $code = str_replace(["\r\n", "\r", "\n", "\t"], '', $code);

        // remove unnecessary spaces before symbols
        return preg_replace('/\s*([=:;,+\-*\/<>\(\)\{\}\[\]])\s*/', '$1', $code);
    }
}
