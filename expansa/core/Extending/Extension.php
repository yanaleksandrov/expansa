<?php

declare(strict_types=1);

namespace Expansa\Extending;

use Expansa\Extending\Traits\ExtensionHelpers;
use Expansa\Extending\Traits\ExtensionTraits;

abstract class Extension
{
    use ExtensionTraits;
    use ExtensionHelpers;

    /**
     * Sets the name of the extension.
     *
     * @param string $name The name of the extension.
     * @return self
     */
    protected function setName(string $name): self
    {
        $this->name = $this->sanitize($name);
        return $this;
    }

    /**
     * Sets the URL for the extension's homepage.
     *
     * @param string $url The URL of the extension.
     * @return self
     */
    protected function setUrl(string $url): self
    {
        $this->url = $this->sanitizeUrl($url);
        return $this;
    }

    /**
     * Sets the description of the extension.
     *
     * @param string $description A brief description of the extension's functionality.
     * @return self
     */
    protected function setDescription(string $description): self
    {
        $this->description = $this->sanitize($description);
        return $this;
    }

    /**
     * Sets the license for the extension.
     *
     * If the license is not already set, it will be assigned.
     *
     * @param string $license The license under which the extension is released.
     * @return self
     */
    protected function setLicense(string $license): self
    {
        $this->license ??= $this->sanitize($license);
        return $this;
    }

    /**
     * Sets the copyright information for the extension.
     *
     * @param string $copyright The copyright information.
     * @return self
     */
    protected function setCopyright(string $copyright): self
    {
        $this->copyright = $this->sanitize($copyright);
        return $this;
    }

    /**
     * Sets the author of the extension.
     *
     * @param string $author The name of the author.
     * @return self
     */
    protected function setAuthor(string $author): self
    {
        $this->author = $this->sanitize($author);
        return $this;
    }

    /**
     * Sets the author's homepage URL.
     *
     * @param string $authorUrl The author's URL.
     * @return self
     */
    protected function setAuthorUrl(string $authorUrl): self
    {
        $this->authorUrl = $this->sanitizeUrl($authorUrl);
        return $this;
    }

    /**
     * Sets the author's email address.
     *
     * @param string $authorEmail The author's email.
     * @return self
     */
    protected function setAuthorEmail(string $authorEmail): self
    {
        $this->authorEmail = $this->sanitize($authorEmail);
        return $this;
    }

    /**
     * Sets the version of the extension.
     *
     * @param string $version The version number of the extension.
     * @return self
     */
    protected function setVersion(string $version): self
    {
        $this->version = $this->sanitize($version);
        return $this;
    }

    /**
     * Sets the minimum required PHP version for the extension.
     *
     * @param string $versionPhp The minimum PHP version.
     * @return self
     */
    protected function setVersionPhp(string $versionPhp): self
    {
        $this->minVersionPhp = $this->sanitize($versionPhp);
        return $this;
    }

    /**
     * Sets the minimum required MySQL version for the extension.
     *
     * @param string $versionDb The minimum database version.
     * @return self
     */
    protected function setVersionMysql(string $versionDb): self
    {
        $this->minVersionDb = $this->sanitize($versionDb);
        return $this;
    }

    /**
     * Sets the Expansa version compatibility for the extension.
     *
     * @param string $versionExpansa The Expansa version.
     * @return self
     */
    protected function setVersionExpansa(string $versionExpansa): self
    {
        $this->minVersionExpansa = $this->sanitize($versionExpansa);
        return $this;
    }
}
