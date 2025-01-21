<?php

declare(strict_types=1);

use Expansa\I18n;
use Expansa\Extensions\Plugin;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setVersion('2024.9')
            ->setName('Query Monitor')
            ->setAuthor('Expansa Team')
            ->setDependencies('ecommerce')
            ->setDescription(I18n::_t('The developer tools panel for Expansa'));
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }

    public function activate(): void
    {
        // TODO: Implement activate() method.
    }

    public function deactivate(): void
    {
        // TODO: Implement deactivate() method.
    }

    public function install(): void
    {
        // TODO: Implement install() method.
    }

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
};
