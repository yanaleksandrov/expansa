<?php

declare(strict_types=1);

use Expansa\I18n;
use Expansa\Extending\Plugin;

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

    public function boot()
    {
        // TODO: Implement boot() method.
    }

    public function activate()
    {
        // TODO: Implement activate() method.
    }

    public function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    public function install()
    {
        // TODO: Implement install() method.
    }

    public function uninstall()
    {
        // TODO: Implement uninstall() method.
    }
};
