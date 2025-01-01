<?php
namespace Grafema\Extensions;

/**
 * This trait provides common properties for Grafema plugins, including metadata
 * such as ID, name, version, and author information.
 *
 * @since 2025.1
 */
trait Traits {

	/**
	 * Unique identifier for the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $id;

	/**
	 * Path to root file of the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $path;

	/**
	 * Name of the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $name;

	/**
	 * Description of the plugin's functionality.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $description;

	/**
	 * URL for the plugin's homepage.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $url = '';

	/**
	 * License under which the plugin is released.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $license = 'GNU General Public License v3.0';

	/**
	 * Copyright information for the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $copyright = '';

	/**
	 * Author of the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $author = '';

	/**
	 * URL to the author's homepage.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $authorUrl = '';

	/**
	 * Author's email address.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $authorEmail = '';

	/**
	 * Version of the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $version;

	/**
	 * Minimum required PHP version for the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $versionPhp = '8.1';

	/**
	 * Minimum required MySQL version for the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $versionMysql = '5.7';

	/**
	 * Grafema version compatibility for the plugin.
	 *
	 * @var string
	 * @since 2025.1
	 */
	public string $versionGrafema = '2025.1';
}
