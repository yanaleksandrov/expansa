<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\Error;
use Expansa\I18n;
use Expansa\Plugin;

/**
 * Provides storage functionality for managing themes & plugins instances.
 *
 * @since 2025.1
 */
class Provider
{
    /**
     * Contains registered instances of themes classes.
     *
     * @since 2025.1
     */
    public static array $themes = [];

    /**
     * Contains registered instances of plugin classes.
     *
     * @since 2025.1
     */
    public static array $plugins = [];

    /**
     *
     *
     * @param callable $callback Callback function used for get plugins paths.
     * @param string   $type
     * @return void
     * @since 2025.1
     */
    protected static function enqueue(callable $callback, string $type): void
    {
        $paths = call_user_func($callback);

        if (is_array($paths)) {
            foreach ($paths as $path) {
                if (! is_file($path)) {
                    continue;
                }

                $extension = require_once $path;
                if (! $extension instanceof Plugin) {
                    continue;
                }

                try {
                    foreach ([ 'name', 'description', 'version' ] as $property) {
                        if (! property_exists($extension, $property) || empty($extension->$property)) {
                            throw new \Exception(I18n::_f('Extension parameter ":propertyName" is required', $property));
                        }
                    }
                } catch (\Exception $e) {
                    new Error('extensions-provider-register', $e->getMessage());
                }

                $extension->id   = dirname(str_replace(EX_PATH, '', $path));
                $extension->path = $path;

                if (in_array($type, [ 'plugins', 'themes' ], true)) {
                    self::${$type}[] = $extension;
                }
            }
        }
    }
}
