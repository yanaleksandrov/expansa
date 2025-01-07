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
            ->setAuthor('Expansa Team')
            ->setName('Expansa Plugin Boilerplate')
            ->setDescription(I18n::_t('Plugin Boilerplate Description'));
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
