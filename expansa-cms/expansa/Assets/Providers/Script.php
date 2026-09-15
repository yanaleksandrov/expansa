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
     * Constructor for the script asset, allowing to define various attributes of the script tag.
     *
     * @param string $uid          Unique ID attribute of the asset.
     * @param string $src          URL or path to the external JavaScript file.
     * @param array  $data         Additional data passed to the asset.
     * @param string $class        CSS class name for the script tag.
     * @param string $type         MIME type of the script (default is 'text/javascript').
     * @param bool   $async        Whether the script should be executed asynchronously.
     * @param bool   $defer        Whether the script should be executed after the document is parsed.
     * @param string $integrity    Integrity hash for the script file to verify its content.
     * @param string $crossorigin  Specifies how to handle cross-origin requests for the script.
     * @param bool   $nomodule     Executes the script only in browsers that do not support modules.
     * @param string $language     Specifies the language of the script (deprecated, not recommended for use).
     * @param string $event        Specifies an event that will trigger the script (deprecated, not recommended for use)
     * @param string $path         Local path to the script for internal reference.
     * @param string $version
     * @param array  $dependencies uid's (or full ids) of assets that are required before this script.
     * @param bool   $toFooter     Output before close body tag.
     */
    public function __construct(
        public string $uid,
        public string $src,
        public array $data = [],
        public string $class = '',
        public string $type = 'text/javascript',
        public bool $async = false,
        public bool $defer = false,
        public string $integrity = '',
        public string $crossorigin = '',
        public bool $nomodule = false,
        public string $language = '',
        public string $event = '',
        public string $path = '',
        public string $version = '',
        public array $dependencies = [],
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
