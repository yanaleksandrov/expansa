<?php

declare(strict_types=1);

namespace Expansa\Assets\Providers;

/**
 * A `<script>` asset: external file, inline code or a combined bundle, with `data` passed
 * to the page as a JS variable named after the uid.
 *
 * @package Expansa\Assets\Providers
 */
final class Script extends AbstractProvider
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
     * @param bool $minify
     * @param bool $inline
     * @return string The HTML script tag with the corresponding attributes.
     */
    public function render(bool $minify = false, bool $inline = false): string
    {
        $return  = $this->preamble();
        $content = ($minify || $inline) ? $this->readContent($minify) : null;

        if ($inline && $content !== null) {
            return $return . $this->renderInline($content);
        }

        $attributes = array_diff_key(get_object_vars($this), array_flip(['uid', 'path', 'data', 'dependencies', 'toFooter']));

        if ($minify && $content !== null) {
            // Null means the write failed (disk full, permissions, ...) - keep the original
            // src rather than link to a cached file that was never actually written.
            $attributes['src'] = self::writeCache($this->uid, $content, 'js') ?? $attributes['src'];
        }

        return $return . sprintf("	<script%s></script>\n", $this->renderAttributes($attributes));
    }

    /**
     * Embed already-computed JS $content directly as a `<script>` tag, keeping only the
     * attributes that still make sense without a src (id, class, type).
     */
    public function renderInline(string $content): string
    {
        $attributes = array_intersect_key(get_object_vars($this), array_flip(['id', 'class', 'type']));

        return sprintf("	<script%s>%s</script>\n", $this->renderAttributes($attributes), $content);
    }

    /**
     * `data`, if any, as `<script>var uid = {...}</script>` before the main tag. Rendered
     * separately rather than folded into the (possibly cached/combined) file content, since
     * `data` is per-render state - baking it into a cached bundle would change that bundle's
     * hash, and thus its filename, on every request where the data differs.
     */
    public function preamble(): string
    {
        $data = json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        return ($this->data && $data)
            ? sprintf("<script>var %s = %s</script>\n", self::variableName($this->uid), $data)
            : '';
    }

    /**
     * No-op: safe JS minification needs real tokenization (a `//` or `/*` may be inside a
     * string or regex literal), which a regex-based pass can't tell apart without risking
     * corrupting the code. Register a custom provider with a real minifier if you need one.
     *
     * @param string $code The JavaScript code to be minified.
     * @return string The code, unchanged.
     */
    public function minify(string $code): string
    {
        return $code;
    }
}
