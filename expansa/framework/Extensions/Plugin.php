<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\Extensions\Contracts\ExtensionSkeleton;

abstract class Plugin extends Extension implements ExtensionSkeleton
{
    public string $type = 'plugin';

    public array $dependencies = [];

    public array $capabilities = [];

    /**
     * Sets the dependencies.
     *
     * @param string $extensionId The unique ID of the plugin.
     * @return self
     */
    protected function setDependencies(string $extensionId): self
    {
        $this->dependencies[] = $this->sanitize($extensionId);
        return $this;
    }

    /**
     * Sets an array of roles and access rights associated with the plugin.
     *
     * @param string $capability User capability.
     * @return self
     */
    protected function addCapability(string $capability): self
    {
        $this->capabilities[] = $this->sanitize($capability);
        return $this;
    }
}
