<?php

declare(strict_types=1);

namespace Expansa\Extensions;

/**
 * This trait provides common properties for Expansa plugins, including metadata
 * such as ID, name, version, and author information.
 *
 * @since 2025.1
 */
trait Traits
{
    /**
     * Unique identifier for the plugin.
     *
     * @var string
     */
    public string $id;

    /**
     * Path to root file of the plugin.
     *
     * @var string
     */
    public string $path;

    /**
     * Name of the plugin.
     *
     * @var string
     */
    public string $name;

    /**
     * Description of the plugin's functionality.
     *
     * @var string
     */
    public string $description;

    /**
     * URL for the plugin's homepage.
     *
     * @var string
     */
    public string $url = '';

    /**
     * License under which the plugin is released.
     *
     * @var string
     */
    public string $license = 'GNU General Public License v3.0';

    /**
     * Copyright information for the plugin.
     *
     * @var string
     */
    public string $copyright = '';

    /**
     * Author of the plugin.
     *
     * @var string
     */
    public string $author = '';

    /**
     * URL to the author's homepage.
     *
     * @var string
     */
    public string $authorUrl = '';

    /**
     * Author's email address.
     *
     * @var string
     */
    public string $authorEmail = '';

    /**
     * Version of the plugin.
     *
     * @var string
     */
    public string $version;

    /**
     * Minimum required PHP version for the plugin.
     *
     * @var string
     */
    public string $versionPhp = '8.1';

    /**
     * Minimum required MySQL version for the plugin.
     *
     * @var string
     */
    public string $versionMysql = '5.7';

    /**
     * Expansa version compatibility for the plugin.
     *
     * @var string
     */
    public string $versionExpansa = EX_VERSION;
}
