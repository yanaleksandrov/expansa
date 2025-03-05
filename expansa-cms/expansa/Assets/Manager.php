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
     * @param string $uid      Unique ID of the resource.
     * @param string $src      Full URL of the resource, or path of the script relative to the Expansa root directory.
     * @param string $provider Type of asset.
     * @param array  $data     List of attributes.
     * @return void
     */
    public function enqueue(string $uid, string $src, string $provider, ...$data): void
    {
        $uid = $this->sanitizeId($uid);
        $src = $this->sanitizeUrl($src);

        if ($uid) {
            $extension = pathinfo($src, PATHINFO_EXTENSION);
            $id        = sprintf('%s-%s', $uid, $extension);

            // add to assets
            if (! isset(self::$assets[$id])) {
                self::$assets[$id] = match ($provider) {
                    'js'    => new Script($uid, $src, ...$data),
                    'css'   => new Link($uid, $src, ...$data),
                    default => null,
                };
            }
        }
    }

    /**
     * Correctly add styles.
     *
     * @param string $uid Unique ID of the resource.
     * @param string $src
     * @param array $data
     * @return void
     */
    public function script(string $uid, string $src, array ...$data): void
    {
        $this->enqueue($uid, $src, 'js', ...$data);
    }

    /**
     * Correctly add styles.
     *
     * @param string $uid Unique ID of the resource.
     * @param string $src
     * @param array $data
     * @return void
     */
    public function style(string $uid, string $src, array ...$data): void
    {
        $this->enqueue($uid, $src, 'css', ...$data);
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
     * @param array $filter An associative array of filtering conditions, where the key is the object
     *                      property name and the value is the expected value or a pattern with `*`.
     * @return void
     * Example filter:
     * [
     *     'uid'      => 'notifications',
     *     'path'     => '*.js',
     *     'toFooter' => true
     * ]
     */
    public function render(array $filter = []): void
    {
        $assets = $this->sortDependencies(self::$assets);

        $assets = array_filter($assets, function ($asset) use ($filter) {
            if (!$asset instanceof Provider) {
                return false;
            }

            if (empty($filter)) {
                return true;
            }

            foreach ($filter as $key => $value) {
                if (!property_exists($asset, $key)) {
                    continue;
                }

                if (is_string($value) && str_contains($value, '*')) {
                    if (!fnmatch($value, $asset->$key)) {
                        return false;
                    }
                } elseif ($asset->$key !== $value) {
                    return false;
                }
            }

            return true;
        });

        foreach ($assets as $asset) {
            echo $asset->render($asset);
        }
    }
}
