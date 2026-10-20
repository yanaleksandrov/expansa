<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Dashboard\Enums\Surface;
use App\Dashboard\Pages\Comment;
use App\Dashboard\Pages\FieldGroups;
use App\Dashboard\Pages\Post;
use App\Dashboard\Pages\Profile;
use App\Dashboard\Pages\SignIn;
use App\Dashboard\Pages\SiteHealth;
use App\Dashboard\Pages\Tables;
use App\Dashboard\Pages\Updates;
use App\Facades\Dashboard;
use App\Models\User;
use App\Tables\Plugins;
use App\Tables\PluginsInstall;
use App\Tables\Terms;
use App\Tables\Themes;
use App\Tables\Users;
use Expansa\Http\Request;

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
        Dashboard::page(
            slug: 'profile',
            title: fn (array $data) => match (true) {
                $data['isNew']  => t('Add New User'),
                $data['isSelf'] => t('Settings'),
                default         => $data['user']->showname ?: $data['user']->login,
            },
            // the account of another user is one of the users
            menu: fn (Request $request) => isset($request->query['new']) || ($request->getInt('id') > 0 && $request->getInt('id') !== User::current()?->id) ? 'users' : 'profile',
        );
        Dashboard::page(
            slug: 'comment',
            can: 'manage_comments',
            title: fn () => t('Edit Comment'),
            surface: Surface::Muted,
            menu: 'edit?table=comments',
        );
        Dashboard::page(
            slug: 'post',
            can: 'types_edit',
            title: fn (array $data) => match (true) {
                $data['post']->id === 0     => $data['type']->labelAdd,
                $data['post']->title !== '' => $data['post']->title,
                default                     => $data['type']->labelEdit,
            },
            surface: Surface::Muted,
            menu: fn (Request $request) => 'edit?table=' . ($request->getString('type') ?: 'pages'),
        );
        Dashboard::page('edit', view: Tables::view(...));
        Dashboard::page('users', view: 'screens/edit');
        Dashboard::page('plugins-install', surface: Surface::Muted);
        Dashboard::page('site-health', surface: Surface::Muted);
        Dashboard::page('updates', surface: Surface::Muted);

        Dashboard::data('sign-in', SignIn::data(...));
        Dashboard::data('profile', Profile::data(...));
        Dashboard::data('comment', Comment::data(...));
        Dashboard::data('post', Post::data(...));
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
