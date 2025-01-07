<?php

declare(strict_types=1);

namespace Expansa\Extending;

use Expansa\I18n;
use Expansa\Extending\Contracts\ExtensionSkeleton;
use Expansa\Extending\Exception\RequiredPropertyException;

class ExtensionsManager
{
    /**
     * Contains registered instances of plugin classes.
     */
    public static array $extensions = [];

    /**
     * Register new extension.
     *
     * @param callable $callback Callback function used for get extensions paths.
     * @return void
     */
    public function boot(callable $callback): void
    {
        self::enqueue($callback, 'plugins');

        foreach (self::$extensions as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->boot();
        }
    }

    /**
     * Activate all registered extensions.
     *
     * Calls the `activate()` method on each registered plugin, allowing them to perform necessary initialization tasks.
     */
    public function activate(): void
    {
        foreach (self::$extensions as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->activate();
        }
    }

    /**
     * Deactivate all registered extensions.
     *
     * Calls the `deactivate()` method on each registered plugin, allowing
     * them to clean up resources or undo changes made during activation.
     */
    public function deactivate(): void
    {
        foreach (self::$extensions as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->deactivate();
        }
    }

    /**
     * Install all registered extensions.
     *
     * Calls the `install()` method on each registered plugin, allowing them to perform installation tasks.
     */
    public function install(): void
    {
        foreach (self::$extensions as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->install();
        }
    }

    /**
     * Uninstall all registered extensions.
     *
     * Calls the `uninstall()` method on each registered plugin, allowing
     * them to clean up resources or remove associated assets.
     */
    public function uninstall(): void
    {
        foreach (self::$extensions as $extension) {
            $extension instanceof ExtensionSkeleton && $extension->uninstall();
        }
    }

    /**
     * Enqueue extensions from paths.
     *
     * @param callable $callback Callback function used for get plugins paths.
     * @return void
     */
    protected function enqueue(callable $callback): void
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
