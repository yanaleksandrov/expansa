<?php

declare(strict_types=1);

namespace Expansa\Extensions\Contracts;

/**
 * Lifecycle of a plugin or a theme: Manager calls these methods for every loaded extension.
 *
 * @package Expansa\Extensions\Contracts
 */
interface Extension
{
    /**
     * Unique identifier for the extension, set by Manager on load.
     */
    public string $id {
        get;
        set;
    }

    /**
     * Path to root file of the extension, set by Manager on load.
     */
    public string $path {
        get;
        set;
    }

    /**
     * Kind of the extension: `plugin` or `theme`.
     */
    public string $type { get; }

    /**
     * Declare what the plugin provides (post types, roles, hooks), in the "extensions" lifecycle phase.
     * Runs for every active extension before any boot().
     */
    public function register(): void;

    /**
     * Launch the plugin, in the "booted" lifecycle phase: every extension is registered and the context is known.
     */
    public function boot(): void;

    /**
     * Activate action the plugin.
     *
     * This method is responsible for activating the plugin.
     * It typically performs necessary initialization tasks and sets up any required resources or configurations.
     */
    public function activate(): void;

    /**
     * Deactivate action the plugin.
     *
     * This method is responsible for deactivating the plugin.
     * It is called when the plugin is being disabled or turned off.
     * It usually involves cleaning up resources, unregistering hooks, or undoing any changes made during activation.
     */
    public function deactivate(): void;

    /**
     * Install action the plugin.
     *
     * This method is responsible for installing the plugin.
     */
    public function install(): void;

    /**
     * Uninstall action the plugin.
     *
     * This method is responsible for uninstalling the plugin.
     * It is called when the plugin is being completely removed from the system.
     * It typically involves removing any database tables, files, or other assets associated with the plugin.
     */
    public function uninstall(): void;
}
