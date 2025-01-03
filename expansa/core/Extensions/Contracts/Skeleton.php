<?php

declare(strict_types=1);

namespace Expansa\Extensions\Contracts;

/**
 * Interface Skeleton.
 *
 * The Skeleton defines the contract for a plugin in an Expansa CMS. It serves as a
 * blueprint for implementing plugins and ensures consistency across different plugins.
 */
interface Skeleton
{
    /**
     * Launch the plugin.
     */
    public static function launch();

    /**
     * Activate action the plugin.
     *
     * This method is responsible for activating the plugin.
     * It typically performs necessary initialization tasks and sets up any required resources or configurations.
     */
    public static function activate();

    /**
     * Deactivate action the plugin.
     *
     * This method is responsible for deactivating the plugin.
     * It is called when the plugin is being disabled or turned off.
     * It usually involves cleaning up resources, unregistering hooks, or undoing any changes made during activation.
     */
    public static function deactivate();

    /**
     * Install action the plugin.
     *
     * This method is responsible for installing the plugin.
     */
    public static function install();

    /**
     * Uninstall action the plugin.
     *
     * This method is responsible for uninstalling the plugin.
     * It is called when the plugin is being completely removed from the system.
     * It typically involves removing any database tables, files, or other assets associated with the plugin.
     */
    public static function uninstall();
}
