<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\Extensions\Contracts\Extension;

/**
 * Base of a plugin: `plugins/<slug>/index.php` returns an anonymous subclass.
 *
 * @package Expansa\Extensions
 */
abstract class Plugin extends AbstractExtension implements Extension
{
    public string $type = 'plugin';

    public array $dependencies = [];

    public array $capabilities = [];

    /**
     * Sets the dependencies.
     *
     * @param string $extensionId The unique ID of the plugin.
     * @return static
     */
    protected function setDependencies(string $extensionId): static
    {
        $this->dependencies[] = $this->sanitize($extensionId);
        return $this;
    }

    /**
     * Sets an array of roles and access rights associated with the plugin.
     *
     * @param string $capability User capability.
     * @return static
     */
    protected function addCapability(string $capability): static
    {
        $this->capabilities[] = $this->sanitize($capability);
        return $this;
    }
}
