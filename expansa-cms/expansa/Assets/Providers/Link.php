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
     * @param array  $dependencies   uid's (or full ids) of assets that must be output before this one.
     * @param bool   $toFooter       Output before close body tag.
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
        public string $version = '',
        public array $dependencies = [],
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
