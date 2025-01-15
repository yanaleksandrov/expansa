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
     * @param string $uid         Unique ID attribute of the asset.
     * @param string $src         URL or path to the external JavaScript file.
     * @param array  $data        Additional data passed to the asset.
     * @param string $class       CSS class name for the script tag.
     * @param string $type        MIME type of the script (default is 'text/javascript').
     * @param bool   $async       Whether the script should be executed asynchronously.
     * @param bool   $defer       Whether the script should be executed after the document is parsed.
     * @param string $integrity   Integrity hash for the script file to verify its content.
     * @param string $crossorigin Specifies how to handle cross-origin requests for the script.
     * @param bool   $nomodule    Executes the script only in browsers that do not support modules.
     * @param string $language    Specifies the language of the script (deprecated, not recommended for use).
     * @param string $event       Specifies an event that will trigger the script (deprecated, not recommended for use).
     * @param string $path        Local path to the script for internal reference.
     * @param array  $dependencies Array of assets that are required before this script.
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
        public string $id = '',
        public string $version = '',
        public array $dependencies = []
    )
    {
        $this->path = $this->toPath($src);
        $this->id   = sprintf('%s-%s', $this->uid, pathinfo($this->path, PATHINFO_EXTENSION));

        foreach ($data as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * Renders the script tag with the specified attributes and optional data.
     *
     * @param Provider $asset The asset to be rendered.
     * @return string The HTML script tag with the corresponding attributes.
     */
    public function render(Provider $asset): string
    {
        $key  = $this->sanitizeConst($asset->uid);
        $atts = array_diff_key((array) $asset, array_flip(['uid', 'path', 'data', 'dependencies']));
        $data = json_encode($asset->data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        $return = '';
        if ($asset->data && $data) {
            $return = sprintf("<script>const %s = %s</script>\n", $key, $data);
        }
        return $return . sprintf("	<script%s></script>\n", $this->sanitizeAttributes($atts));
    }

    /**
     * Minifies the JavaScript code by removing comments and unnecessary spaces.
     *
     * @param string $code The JavaScript code to be minified.
     * @return string The minified JavaScript code.
     */
    public function minify(string $code): string
    {
        return $code;
    }
}
