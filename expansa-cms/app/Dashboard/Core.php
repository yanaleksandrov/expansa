<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Dashboard\Pages\FieldGroups;
use App\Dashboard\Pages\SignIn;
use App\Dashboard\Pages\SiteHealth;
use App\Dashboard\Pages\Tables;
use App\Dashboard\Pages\Updates;
use App\Dashboard\Pages\User;
use App\Facades\Dashboard;
use App\Tables\Plugins;
use App\Tables\PluginsInstall;
use App\Tables\Terms;
use App\Tables\Themes;
use App\Tables\Users;

/**
 * Pages of the dashboard itself, registered with the API of plugins before they boot, so a plugin
 * changes them the same way. Titles are closures: nothing is translated until a page is shown.
 *
 * @package App\Dashboard
 */
final class Core
{
    /**
     * Register the pages and their data providers.
     *
     * @return void
     */
    public static function register(): void
    {
        Dashboard::page('403', title: fn () => t('Access Denied'));
        Dashboard::page('404', title: fn () => t('Page Not Found'));
        Dashboard::page('sign-in', title: fn () => t('Sign In'));
        Dashboard::page('sign-up', title: fn () => t('Sign Up'));
        Dashboard::page('reset-password', title: fn () => t('Reset Password'));
        Dashboard::page('installed', title: fn () => t('Installed'));
        Dashboard::page('user', can: 'users_edit');
        Dashboard::page('edit', view: Tables::view(...));
        Dashboard::page('users', view: 'screens/edit');

        Dashboard::data('sign-in', SignIn::data(...));
        Dashboard::data('user', User::data(...));
        Dashboard::data('field-groups', FieldGroups::data(...));
        Dashboard::data('site-health', SiteHealth::data(...));
        Dashboard::data('updates', Updates::data(...));
        Dashboard::data('edit', Tables::data(...));
        Dashboard::data('users', fn () => ['table' => new Users()]);
        Dashboard::data('plugins', fn () => ['table' => new Plugins()]);
        Dashboard::data('plugins-install', fn () => ['table' => new PluginsInstall()]);
        Dashboard::data('themes', fn () => ['table' => new Themes()]);
        Dashboard::data('terms', fn () => ['table' => new Terms()]);
    }
}
