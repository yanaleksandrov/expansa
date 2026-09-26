<?php

declare(strict_types=1);

namespace Expansa\Assets\Providers;

use Expansa\Assets\Abstracts\Provider;

/**
 * Class Script
 *
 * This class represents a script asset and provides functionality for rendering
 * and minifying script tags with various attributes. It allows specifying attributes
 * such as async, defer, type, and more.
 *
 * @package Expansa\Assets\Providers
 */
class Script extends Provider
{
    /**
     * Computes `path` from `src`; `data` entries override matching properties except `id`.
     */
    public function __construct(

        /**
         * Unique ID attribute of the asset.
         */
        public string $uid,

        /**
         * URL or path to the external JavaScript file.
         */
        public string $src,

        /**
         * Additional data passed to the asset.
         */
        public array $data = [],

        /**
         * CSS class name for the script tag.
         */
        public string $class = '',

        /**
         * MIME type of the script.
         */
        public string $type = 'text/javascript',

        /**
         * Whether the script should be executed asynchronously.
         */
        public bool $async = false,

        /**
         * Whether the script should be executed after the document is parsed.
         */
        public bool $defer = false,

        /**
         * Integrity hash for the script file to verify its content.
         */
        public string $integrity = '',

        /**
         * Specifies how to handle cross-origin requests for the script.
         */
        public string $crossorigin = '',

        /**
         * Executes the script only in browsers that do not support modules.
         */
        public bool $nomodule = false,

        /**
         * Specifies the language of the script (deprecated, not recommended for use).
         */
        public string $language = '',

        /**
         * Specifies an event that will trigger the script (deprecated, not recommended for use).
         */
        public string $event = '',

        /**
         * Local path to the script for internal reference.
         */
        public string $path = '',

        /**
         * Asset version; not added to the URL (cached copies use content-hashed names) and ignored when checking if the asset can be bundled.
         */
        public string $version = '',

        /**
         * uid's (or full ids) of assets that are required before this script.
         */
        public array $dependencies = [],

        /**
         * Output before close body tag.
         */
        public bool $toFooter = true,
    )
    {
        $this->path = self::toPath($src);

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
        get => sprintf('%s-%s', $this->uid, pathinfo($this->path ?: $this->src, PATHINFO_EXTENSION));
    }

    /**
     * Renders the script tag with the specified attributes and optional data. $inline embeds
     * the content directly instead of a `src`; $minify runs it through minify() first either
     * way. No effect on an asset with no local file to read.
     *
     * @param Provider $asset The asset to be rendered.
     * @param bool     $minify
     * @param bool     $inline
     * @return string The HTML script tag with the corresponding attributes.
     */
    public function render(Provider $asset, bool $minify = false, bool $inline = false): string
    {
        $return  = $this->preamble($asset);
        $content = ($minify || $inline) ? $this->readContent($asset, $minify) : null;

        if ($inline && $content !== null) {
            return $return . $this->renderInline($asset, $content);
        }

        $attributes = array_diff_key(get_object_vars($asset), array_flip(['uid', 'path', 'data', 'dependencies', 'toFooter']));

        if ($minify && $content !== null) {
            // Null means the write failed (disk full, permissions, ...) - keep the original
            // src rather than link to a cached file that was never actually written.
            $attributes['src'] = $this->cacheMinifiedFile($asset->uid, $content, 'js') ?? $attributes['src'];
        }

        return $return . sprintf("	<script%s></script>\n", $this->sanitizeAttributes($attributes));
    }

    /**
     * Embed already-computed JS $content directly as a `<script>` tag, keeping only the
     * attributes that still make sense without a src (id, class, type).
     */
    public function renderInline(Provider $asset, string $content): string
    {
        $attributes = array_intersect_key(get_object_vars($asset), array_flip(['id', 'class', 'type']));

        return sprintf("	<script%s>%s</script>\n", $this->sanitizeAttributes($attributes), $content);
    }

    /**
     * `data`, if any, as `<script>var uid = {...}</script>` before the main tag. Rendered
     * separately rather than folded into the (possibly cached/combined) file content, since
     * `data` is per-render state - baking it into a cached bundle would change that bundle's
     * hash, and thus its filename, on every request where the data differs.
     */
    public function preamble(Provider $asset): string
    {
        $data = json_encode($asset->data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        return ($asset->data && $data)
            ? sprintf("<script>var %s = %s</script>\n", $this->sanitizeConst($asset->uid), $data)
            : '';
    }

    /**
     * No-op: safe JS minification needs real tokenization (a `//` or `/*` may be inside a
     * string or regex literal), which a regex-based pass can't tell apart without risking
     * corrupting the code. Override this (or register a custom provider) with a real
     * minifier/tokenizer if you need actual JS minification.
     *
     * @param string $code The JavaScript code to be minified.
     * @return string The code, unchanged.
     */
    public function minify(string $code): string
    {
        return $code;
    }
}
