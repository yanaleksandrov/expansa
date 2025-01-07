<?php

declare(strict_types=1);

namespace Expansa\Extending\Traits;

/**
 * This trait provides common properties for Expansa extensions, including metadata
 * such as ID, name, version, and author information.
 */
trait ExtensionTraits
{
    /**
     * Unique identifier for the extension.
     *
     * @var string
     */
    public string $id;

    /**
     * Path to root file of the extension.
     *
     * @var string
     */
    public string $path;

    /**
     * Name of the extension.
     *
     * @var string
     */
    public string $name;

    /**
     * Description of the extension's functionality.
     *
     * @var string
     */
    public string $description;

    /**
     * URL for the extension's homepage.
     *
     * @var string
     */
    public string $url = '';

    /**
     * License under which the extension is released.
     *
     * @var string
     */
    public string $license = 'GNU General Public License v3.0';

    /**
     * Copyright information for the extension.
     *
     * @var string
     */
    public string $copyright = '';

    /**
     * Author of the extension.
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
     * Version of the extension.
     *
     * @var string
     */
    public string $version;

    /**
     * Minimum required PHP version for the extension.
     *
     * @var string
     */
    public string $minVersionPhp = '8.1';

    /**
     * Minimum required database version for the extension.
     *
     * @var string
     */
    public string $minVersionDb = '5.7';

    /**
     * Expansa version compatibility for the extension.
     *
     * @var string
     */
    public string $minVersionExpansa = EX_VERSION;
}
