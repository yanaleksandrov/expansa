<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\Extensions\Contracts\ExtensionSkeleton;
use Expansa\Extensions\Exceptions\RequiredPropertyException;

class Manager
{
    /**
     * Contains registered instances of plugin classes.
     */
    public static array $extensions = [];

    /**
     * Directory the extension ids are relative to, with a trailing slash.
     */
    private static string $root = '';

    /**
     * Get extensions list.
     *
     * @param string $type
     * @return array
     */
    /**
     * Set the directory that holds the "plugins" and "themes" folders.
     */
    public function configure(string $root): void
    {
        self::$root = rtrim($root, '/\\') . '/';
    }

    public function get(string $type): array
    {
         return self::$extensions[$type] ?? [];
    }

    /**
     * Call register() of every enqueued extension of the type.
     *
     * @param string $type
     * @return void
     */
    public function register(string $type): void
    {
        foreach (self::$extensions[$type] ?? [] as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->register();
        }
    }

    /**
     * Call boot() of every enqueued extension of the type.
     *
     * @param string $type
     * @return void
     */
    public function boot(string $type): void
    {
        foreach (self::$extensions[$type] ?? [] as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->boot();
        }
    }

    /**
     * Activate all registered extensions.
     * Calls the `activate()` method on each registered plugin, allowing them to perform necessary initialization tasks.
     *
     * @param string $type
     * @return void
     */
    public function activate(string $type): void
    {
        foreach (self::$extensions[$type] ?? [] as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->activate();
        }
    }

    /**
     * Deactivate all registered extensions.
     * Calls the `deactivate()` method on each registered plugin, allowing
     * them to clean up resources or undo changes made during activation.
     *
     * @param string $type
     * @return void
     */
    public function deactivate(string $type): void
    {
        foreach (self::$extensions[$type] ?? [] as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->deactivate();
        }
    }

    /**
     * Install all registered extensions.
     * Calls the `install()` method on each registered plugin, allowing them to perform installation tasks.
     *
     * @param string $type
     * @return void
     */
    public function install(string $type): void
    {
        foreach (self::$extensions[$type] ?? [] as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->install();
        }
    }

    /**
     * Uninstall all registered extensions.
     * Calls the `uninstall()` method on each registered plugin, allowing
     * them to clean up resources or remove associated assets.
     *
     * @param string $type
     * @return void
     */
    public function uninstall(string $type): void
    {
        foreach (self::$extensions[$type] ?? [] as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->uninstall();
        }
    }

    /**
     * Entry files of extensions by id, e.g. "plugins/seo" => "{root}plugins/seo/index.php".
     * Ids other than "plugins/{dir}" or "themes/{dir}" are ignored, so a stored list can't point elsewhere.
     *
     * @param array $ids
     * @return string[]
     */
    private function paths(array $ids): array
    {
        $paths = [];
        foreach ($ids as $id) {
            if (is_string($id) && preg_match('#^(plugins|themes)/[a-z0-9_-]+$#i', $id)) {
                $paths[] = self::$root . "$id/index.php";
            }
        }

        return $paths;
    }

    /**
     * Load the extensions by id, e.g. "plugins/seo"; ids other than "plugins/{dir}" or "themes/{dir}" are ignored.
     *
     * @param string[] $ids
     * @return void
     */
    public function load(array $ids): void
    {
        foreach ($this->paths($ids) as $path) {
            if (! is_file($path)) {
                continue;
            }

            $extension = require_once $path;
            if (! $extension instanceof ExtensionSkeleton) {
                continue;
            }

            try {
                foreach (['name', 'description', 'version'] as $property) {
                    if (property_exists($extension, $property) && !empty($extension->$property)) {
                        continue;
                    }

                    throw new RequiredPropertyException(
                        t('Extension parameter ":propertyName" is required', $property)
                    );
                }
            } catch (RequiredPropertyException $e) {

            }

            $extension->id   = dirname(str_replace(self::$root, '', $path));
            $extension->path = $path;

            self::$extensions[$extension->type][] = $extension;
        }
    }
}
