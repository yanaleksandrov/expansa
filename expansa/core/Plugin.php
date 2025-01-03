<?php

declare(strict_types=1);

namespace Expansa;

/**
 * Represents an Expansa plugin, encapsulating its metadata and providing methods
 * to set various properties such as name, URL, description, and versioning.
 *
 * This class utilizes the Traits to inherit common properties associated with plugins.
 */
abstract class Plugin implements Extensions\Contracts\Skeleton
{
    use Extensions\Traits;

    /**
     * Sets the name of the plugin.
     *
     * @param string $name The name of the plugin.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the URL for the plugin's homepage.
     *
     * @param string $url The URL of the plugin.
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Sets the description of the plugin.
     *
     * @param string $description A brief description of the plugin's functionality.
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Sets the license for the plugin.
     *
     * If the license is not already set, it will be assigned.
     *
     * @param string $license The license under which the plugin is released.
     * @return self
     */
    public function setLicense(string $license): self
    {
        $this->license ??= $license;
        return $this;
    }

    /**
     * Sets the copyright information for the plugin.
     *
     * @param string $copyright The copyright information.
     * @return self
     */
    public function setCopyright(string $copyright): self
    {
        $this->copyright = $copyright;
        return $this;
    }

    /**
     * Sets the author of the plugin.
     *
     * @param string $author The name of the author.
     * @return self
     */
    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Sets the author's homepage URL.
     *
     * @param string $authorUrl The author's URL.
     * @return self
     */
    public function setAuthorUrl(string $authorUrl): self
    {
        $this->authorUrl = $authorUrl;
        return $this;
    }

    /**
     * Sets the author's email address.
     *
     * @param string $authorEmail The author's email.
     * @return self
     */
    public function setAuthorEmail(string $authorEmail): self
    {
        $this->authorEmail = $authorEmail;
        return $this;
    }

    /**
     * Sets the version of the plugin.
     *
     * @param string $version The version number of the plugin.
     * @return self
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Sets the minimum required PHP version for the plugin.
     *
     * @param string $versionPhp The minimum PHP version.
     * @return self
     */
    public function setVersionPhp(string $versionPhp): self
    {
        $this->versionPhp = $versionPhp;
        return $this;
    }

    /**
     * Sets the minimum required MySQL version for the plugin.
     *
     * @param string $versionMysql The minimum MySQL version.
     * @return self
     */
    public function setVersionMysql(string $versionMysql): self
    {
        $this->versionMysql = $versionMysql;
        return $this;
    }

    /**
     * Sets the Expansa version compatibility for the plugin.
     *
     * @param string $versionExpansa The Expansa version.
     * @return self
     */
    public function setVersionExpansa(string $versionExpansa): self
    {
        $this->versionExpansa = $versionExpansa;
        return $this;
    }
}
