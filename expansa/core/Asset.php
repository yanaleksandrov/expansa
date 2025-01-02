<?php

declare(strict_types=1);

namespace Expansa;

/**
 * Allows assets (CSS, JS, etc.) to be included throughout the
 * application, and then outputted later based on dependencies.
 * This makes sure all assets will be included in the correct
 * order, no matter what order they are defined in.
 *
 * @version   2025.1
 */
final class Asset
{
    /**
     * Assets list.
     */
    private static array $assets = [];

    /**
     * Correctly add JS scripts and CSS styles to the page.
     *
     * To connect files, it is better to use this function than to specify the path to the file directly.
     * This will allow you to combine JS or CSS files into one without any problems.
     * Get rid of script conflicts when the dependent script is connected to the main one.
     *
     * @param string $id      ID of the resource. Should be unique.
     * @param string $src     Full URL of the resource, or path of the script relative to the Expansa root directory.
     * @param array  $args    List of attributes.
     * @param string $version Version of file.
     *
     * @return void
     */
    public static function enqueue(string $id, string $src, array $args = [], string $version = EX_VERSION): void
    {
        $id  = Asset\Sanitizer::id($id);
        $src = Asset\Sanitizer::url($src);

        if ($id && $src) {
            $extension = pathinfo($src, PATHINFO_EXTENSION);
            $uid      = sprintf('%s-%s', $id, $extension);

            if ($version) {
                $src = sprintf('%s?%s', $src, http_build_query([ 'v' => $version ]));
            }

            /**
             * Filters the data array asset arguments.
             *
             * @since 2025.1
             */
            $args = Hook::call('expansa_asset_enqueue_args', $args, $uid, $src);

            // add to assets
            if (! isset(self::$assets[ $uid ])) {
                self::$assets[ $uid ] = match ($extension) {
                    'js'  => ( new Asset\ProviderJS() )->add($id, $src, $args),
                    'css' => ( new Asset\ProviderCSS() )->add($id, $src, $args),
                };
            }
        }
    }

    /**
     * Override data of exist asset.
     *
     * @param string $id
     * @param string $src
     * @param array $args
     * @param string $version
     *
     * @return void
     */
    public static function override(string $id, string $src, array $args = [], string $version = EX_VERSION): void
    {
        self::dequeue($id);
        self::enqueue($id, $src, $args, $version);
    }

    /**
     * Remove a previously enqueued source.
     *
     * @param string $id The unique id of the asset which to be deleted
     *
     * @return void
     */
    public static function dequeue(string $id): void
    {
        if (isset(self::$assets[$id])) {
            unset(self::$assets[$id]);
        }
    }

    /**
     * Get enqueued assets.
     *
     * @param string $id
     * @return array
     */
    public static function get(string $id = ''): array
    {
        if ($id) {
            return self::$assets[ $id ] ?? [];
        }
        return self::$assets;
    }

    /**
     * Add data to resource.
     *
     * @param string $id
     * @param array $data
     */
    public static function attach(string $id, array $data): void
    {
    }

    /**
     * Render HTML tags for include assets.
     *
     * @param  string $pattern
     * @return void
     */
    public static function render(string $pattern = ''): void
    {
        $assets = Asset\Dependency::sort(self::$assets);

        foreach ($assets as $asset) {
            $type = $asset['type'] ?? '';
            $path = $asset['path'] ?? '';

            if (! empty($pattern) && ! fnmatch($pattern, $path)) {
                continue;
            }

            echo match ($type) {
                'js'  => ( new Asset\ProviderJS() )->plug($asset),
                'css' => ( new Asset\ProviderCSS() )->plug($asset),
            };
        }
    }
}
