<?php

declare(strict_types=1);

namespace Expansa\Assets\Abstracts;

use Expansa\Assets\Support\AssetHandler;

/**
 * Base contract every asset provider (Link, Script, or a custom one registered via
 * Manager::provider()) must implement.
 *
 * @property-read string $uid          Unique id of the resource, as passed to Manager::enqueue().
 * @property-read string $id           Composite id under which the asset is stored: `{uid}-{extension}`.
 * @property-read string $path         Absolute filesystem path of the asset, or '' when it is external.
 * @property-read array  $dependencies uid's (or full ids) that must be rendered before this asset.
 * @property-read bool   $toFooter     Whether the asset renders in the footer group instead of the head.
 */
abstract class Provider
{
    use AssetHandler;

    /**
     * Plug & render an asset. $minify/$inline are decided at the {@see \Expansa\Assets\Manager::render()}
     * call site, not stored on the asset - they're rendering concerns, not asset properties.
     *
     * @param bool $minify Run the asset's own {@see self::minify()} over its content first.
     * @param bool $inline Embed the (possibly minified) content instead of linking to the file -
     *                     no effect on an asset with no local file (an external URL always
     *                     renders as a normal link).
     */
    abstract public function render(Provider $asset, bool $minify = false, bool $inline = false): string;

    /**
     * Wrap already-computed $content in this provider's inline tag shape (`<style>`, `<script>`,
     * ...), bypassing $asset's own href/src/path - used to inline a *combined* bundle, which has
     * no file of its own to read back. Defaults to a normal {@see self::render()}; override only
     * if "inline content" means something for your provider (not everything can be, e.g. a font).
     */
    public function renderInline(Provider $asset, string $content): string
    {
        return $this->render($asset);
    }

    /**
     * Markup this asset needs alongside its tag, rendered separately whether it's tagged on its
     * own or folded into a {@see \Expansa\Assets\Manager::render()} `combine: true` bundle - e.g.
     * Script's inline `data` dump. Kept out of the (cached, content-addressed) file itself: data
     * that varies per request/user would otherwise change the bundle's hash on every render,
     * defeating the cache and leaving near-duplicate files behind in cache/assets. Empty by
     * default; only Script currently has anything to say here.
     */
    public function preamble(Provider $asset): string
    {
        return '';
    }

    /**
     * Minifies the provided code, typically used to reduce the size of CSS or JavaScript.
     */
    abstract public function minify(string $code): string;
}
