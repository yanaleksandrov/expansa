<?php

use Expansa\Db;
use Expansa\I18n;
use Expansa\Is;
use Expansa\Hook;
use Expansa\Debug;
use Expansa\Extensions\Theme;

return new class extends Theme
{
    public function __construct()
    {
        $this
            ->setVersion('2024.9')
            ->setName('Smarto')
            ->setAuthor('Expansa Team')
            ->setDescription(I18n::_t('The developer tools panel for Expansa'));
    }

    public function boot(): void
    {
        if (! Is::dashboard()) {
            return;
        }
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
