<?php

declare(strict_types=1);

namespace Expansa\Extensions\Internal;

use Expansa\Extensions\Traits\HasMetadata;
use Expansa\Extensions\Traits\Sanitizes;

/**
 * Base of Plugin and Theme: metadata set by fluent setters, which sanitize the values.
 *
 * @internal
 * @package Expansa\Extensions\Internal
 */
abstract class AbstractExtension
{
    use HasMetadata;
    use Sanitizes;

    /**
     * Nothing to declare by default: most extensions only need boot().
     */
    public function register(): void
    {
    }

    /**
     * Sets the name of the extension.
     *
     * @param string $name The name of the extension.
     * @return static
     */
    protected function setName(string $name): static
    {
        $this->name = $this->sanitize($name);

        return $this;
    }

    /**
     * Sets the URL for the extension's homepage.
     *
     * @param string $url The URL of the extension.
     * @return static
     */
    protected function setUrl(string $url): static
    {
        $this->url = $this->sanitizeUrl($url);

        return $this;
    }

    /**
     * Sets the description of the extension.
     *
     * @param string $description A brief description of the extension's functionality.
     * @return static
     */
    protected function setDescription(string $description): static
    {
        $this->description = $this->sanitize($description);

        return $this;
    }

    /**
     * Sets the license for the extension.
     *
     * @param string $license The license under which the extension is released.
     * @return static
     */
    protected function setLicense(string $license): static
    {
        $this->license = $this->sanitize($license);

        return $this;
    }

    /**
     * Sets the copyright information for the extension.
     *
     * @param string $copyright The copyright information.
     * @return static
     */
    protected function setCopyright(string $copyright): static
    {
        $this->copyright = $this->sanitize($copyright);

        return $this;
    }

    /**
     * Sets the author of the extension.
     *
     * @param string $author The name of the author.
     * @return static
     */
    protected function setAuthor(string $author): static
    {
        $this->author = $this->sanitize($author);

        return $this;
    }

    /**
     * Sets the author's homepage URL.
     *
     * @param string $authorUrl The author's URL.
     * @return static
     */
    protected function setAuthorUrl(string $authorUrl): static
    {
        $this->authorUrl = $this->sanitizeUrl($authorUrl);

        return $this;
    }

    /**
     * Sets the author's email address.
     *
     * @param string $authorEmail The author's email.
     * @return static
     */
    protected function setAuthorEmail(string $authorEmail): static
    {
        $this->authorEmail = $this->sanitize($authorEmail);

        return $this;
    }

    /**
     * Sets the version of the extension.
     *
     * @param string $version The version number of the extension.
     * @return static
     */
    protected function setVersion(string $version): static
    {
        $this->version = $this->sanitize($version);

        return $this;
    }

    /**
     * Sets the minimum required PHP version for the extension.
     *
     * @param string $versionPhp The minimum PHP version.
     * @return static
     */
    protected function setVersionPhp(string $versionPhp): static
    {
        $this->minVersionPhp = $this->sanitize($versionPhp);

        return $this;
    }

    /**
     * Sets the minimum required MySQL version for the extension.
     *
     * @param string $versionDb The minimum database version.
     * @return static
     */
    protected function setVersionMysql(string $versionDb): static
    {
        $this->minVersionDb = $this->sanitize($versionDb);

        return $this;
    }

    /**
     * Sets the Expansa version compatibility for the extension.
     *
     * @param string $versionExpansa The Expansa version.
     * @return static
     */
    protected function setVersionExpansa(string $versionExpansa): static
    {
        $this->minVersionExpansa = $this->sanitize($versionExpansa);

        return $this;
    }
}
