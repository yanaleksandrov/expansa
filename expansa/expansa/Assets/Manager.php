<?php

declare(strict_types=1);

namespace Expansa\Assets;

use Expansa\Assets\Abstracts\Provider;
use Expansa\Assets\Support\AssetHandler;
use Expansa\Assets\Providers\Link;
use Expansa\Assets\Providers\Script;

/**
 * Allows assets (CSS, JS, etc.) to be included throughout the application, and then outputted
 * later based on dependencies. This makes sure all assets will be included in the correct
 * order, no matter what order they are defined in.
 */
class Manager
{
    use AssetHandler;

    /**
     * Assets list.
     */
    public static array $assets = [];

    /**
     * Correctly add JS scripts and CSS styles to the page.
     * To connect files, it is better to use this function than to specify the path to the file directly.
     * This will allow you to combine JS or CSS files into one without any problems.
     * Get rid of script conflicts when the dependent script is connected to the main one.
     *
     * @param string $uid  Unique ID of the resource.
     * @param string $src  Full URL of the resource, or path of the script relative to the Expansa root directory.
     * @param array  $data List of attributes.
     * @return void
     */
    public function enqueue(string $uid, string $src, ...$data): void
    {
        $uid = $this->sanitizeId($uid);
        $src = $this->sanitizeUrl($src);

        if ($uid) {
            $extension = pathinfo($src, PATHINFO_EXTENSION);
            $id        = sprintf('%s-%s', $uid, $extension);

            // add to assets
            if (! isset(self::$assets[$id])) {
                self::$assets[$id] = match ($extension) {
                    'js'  => new Script($uid, $src, ...$data),
                    'css' => new Link($uid, $src, ...$data),
                };
            }
        }
    }

    /**
     * Override data of exist asset.
     *
     * @param string $uid
     * @param string $src
     * @param array $data
     *
     * @return void
     */
    public function override(string $uid, string $src, ...$data): void
    {
        $this->dequeue($uid);
        $this->enqueue($uid, $src, ...$data);
    }

    /**
     * Remove a previously enqueued source.
     *
     * @param string $uid The unique id of the asset which to be deleted
     *
     * @return void
     */
    public function dequeue(string $uid): void
    {
        unset(self::$assets[$uid]);
    }

    /**
     * Get enqueued assets.
     *
     * @param string $uid
     * @return array
     */
    public function get(string $uid = ''): array
    {
        return $uid ? self::$assets[$uid] ?? [] : self::$assets;
    }

    /**
     * Render HTML tags for include assets.
     *
     * @param  string $pattern
     * @return void
     */
    public function render(string $pattern): void
    {
        $assets = $this->sortDependencies(self::$assets);
        foreach ($assets as $asset) {
            if (!$asset instanceof Provider || ! fnmatch($pattern, $asset->path)) {
                continue;
            }
            echo $asset->render($asset);
        }
    }
}
