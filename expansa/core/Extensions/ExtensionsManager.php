<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\I18n;
use Expansa\Extensions\Contracts\ExtensionSkeleton;
use Expansa\Extensions\Exception\RequiredPropertyException;

class ExtensionsManager
{
    /**
     * Contains registered instances of plugin classes.
     */
    public static array $extensions = [];

    /**
     * Register new extension.
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
     * Enqueue extensions from paths.
     *
     * @param callable $callback Callback function used for get plugins paths.
     * @return void
     */
    public function enqueue(callable $callback): void
    {
        $paths = call_user_func($callback);
        if (!is_array($paths)) {
            return;
        }

        foreach ($paths as $path) {
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
                        I18n::_t('Extension parameter ":propertyName" is required', $property)
                    );
                }
            } catch (RequiredPropertyException $e) {

            }

            $extension->id   = dirname(str_replace(EX_PATH, '', $path));
            $extension->path = $path;

            self::$extensions[$extension->type][] = $extension;
        }
    }
}
