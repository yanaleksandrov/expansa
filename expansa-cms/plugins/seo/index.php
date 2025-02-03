<?php

declare(strict_types=1);

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
            ->setDescription(t('The developer tools panel for Expansa'));
    }

    public function boot(): void
    {

    }

    public function activate(): void
    {

    }

    public function deactivate(): void
    {

    }

    public function install(): void
    {

    }

    public function uninstall(): void
    {

    }
};
