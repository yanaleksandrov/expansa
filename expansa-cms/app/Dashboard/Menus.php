<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Models\User;
use Expansa\Builders\Tree;
use Expansa\Facades\Auth;

/**
 * Menus of the dashboard: the main side menu, the panel, the user menu and the bar.
 * A plugin adds its items with Tree::attach() on the same menu ids.
 *
 * @package App\Dashboard
 */
final class Menus
{
    /**
     * Attach the items of the dashboard menus, built when a menu is rendered.
     *
     * @return void
     */
    public static function register(): void
    {
        Tree::attach('dashboard-panel-menu', static fn (Tree $tree) => $tree->append(
            [
                [
                    'id'           => 'users',
                    'url'          => 'users',
                    'title'        => t('Users'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-users-three',
                    'position'     => 600,
                ],
                [
                    'id'           => 'emails',
                    'url'          => "edit?table=email",
                    'title'        => t('Emails'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-mailbox',
                    'position'     => 700,
                ],
                [
                    'id'           => 'tasks',
                    'url'          => 'tasks',
                    'title'        => t('My Plans and Tasks'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-strategy',
                    'position'     => 800,
                ],
            ]
        ));

        Tree::attach('dashboard-user-menu', static fn (Tree $tree) => $tree->append(
            [
                [
                    'id'           => 'profile',
                    'url'          => 'profile',
                    'title'        => t('Settings'),
                    'capabilities' => ['read'],
                    'icon'         => 'ph ph-sliders',
                    'position'     => 100,
                ],
                [
                    'id'       => 'divider-accounts',
                    'title'    => t('Switch account'),
                    'position' => 200,
                ],
                ...self::accounts(300),
                [
                    'id'       => 'add-account',
                    'url'      => url('sign-in?add=1'),
                    'title'    => t('Add Account'),
                    'icon'     => 'ph ph-plus',
                    'position' => 400,
                ],
                [
                    'id'       => 'sign-out-all',
                    'url'      => url('sign-out?all=1'),
                    'title'    => Auth::getAccounts() === [] ? t('Sign Out') : t('Sign Out of All Accounts'),
                    'icon'     => 'ph ph-sign-out',
                    'position' => 500,
                ],
            ]
        ));

        Tree::attach('dashboard-menu-bar', static fn (Tree $tree) => $tree->append(
            [
                [
                    'id'           => 'website',
                    'url'          => '/',
                    'title'        => 'Expansa',
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-user-focus',
                    'position'     => 10,
                ],
                [
                    'id'           => 'updates',
                    'url'          => 'updates',
                    'title'        => 0,
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-clock-clockwise',
                    'position'     => 20,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'comments',
                    'title'        => 0,
                    'capabilities' => ['manage_comments'],
                    'icon'         => 'ph ph-chats',
                    'position'     => 30,
                ],
                [
                    'id'           => 'new',
                    'url'          => 'new',
                    'title'        => t('New'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-plus',
                    'position'     => 40,
                ],
                [
                    'id'           => 'site-health',
                    'url'          => 'site-health',
                    'title'        => '0Q 0.001s 999kb',
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-monitor',
                    'position'     => 50,
                ],
            ]
        ));

        Tree::attach('dashboard-main-menu', static fn (Tree $tree) => $tree->append(
            [
                [
                    'id'       => 'divider-workspace',
                    'title'    => t('Workspace'),
                    'position' => -20,
                ],
                [
                    'id'           => 'chat',
                    'url'          => 'chat',
                    'title'        => t('Chat'),
                    'capabilities' => ['plugins_install'],
                    'icon'         => 'ph ph-chat-circle-text',
                    'position'     => -10,
                ],
                [
                    'id'       => 'divider-content',
                    'title'    => t('Content'),
                    'position' => 10,
                ],
                [
                    'id'           => 'dialogs',
                    'url'          => 'edit?table=comments',
                    'title'        => t('Discussions'),
                    'capabilities' => ['manage_comments'],
                    'icon'         => 'ph ph-chats',
                    'position'     => 200,
                ],
                [
                    'id'           => 'comments',
                    'url'          => 'edit?table=comments',
                    'title'        => t('Comments'),
                    'capabilities' => ['manage_comments'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'dialogs',
                ],
                [
                    'id'           => 'field-groups',
                    'url'          => 'field-groups',
                    'title'        => t('Custom Fields'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-stack',
                    'position'     => 250,
                ],
                [
                    'id'       => 'divider-customization',
                    'title'    => t('Customization'),
                    'position' => 300,
                ],
                [
                    'id'           => 'appearance',
                    'url'          => 'themes',
                    'title'        => t('Appearance'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-paint-bucket',
                    'position'     => 400,
                ],
                [
                    'id'           => 'themes',
                    'url'          => 'themes',
                    'title'        => t('Themes'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'appearance',
                ],
                [
                    'id'           => 'menus',
                    'url'          => 'nav-menu',
                    'title'        => t('Menus'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'appearance',
                ],
                [
                    'id'           => 'plugins',
                    'url'          => 'plugins',
                    'title'        => t('Plugins'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-plug',
                    'position'     => 500,
                ],
                [
                    'id'           => 'installed',
                    'url'          => 'plugins',
                    'title'        => t('Installed'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'plugins',
                ],
                [
                    'id'           => 'install',
                    'url'          => 'plugins-install',
                    'title'        => t('Add New'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'plugins',
                ],
                [
                    'id'           => 'tools',
                    'url'          => 'tools',
                    'title'        => t('Tools'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-hammer',
                    'position'     => 700,
                ],
                [
                    'id'           => 'import',
                    'url'          => 'import',
                    'title'        => t('Import'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'tools',
                ],
                [
                    'id'           => 'export',
                    'url'          => 'export',
                    'title'        => t('Export'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'tools',
                ],
                [
                    'id'           => 'translation',
                    'url'          => 'edit?table=translation',
                    'title'        => t('Multilingual'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-translate',
                    'position'     => 800,
                ],
                [
                    'id'           => 'translations',
                    'url'          => 'edit?table=translation',
                    'title'        => t('Translations'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'translation',
                ],
                [
                    'id'           => 'translations-settings',
                    'url'          => 'multilingual-settings',
                    'title'        => t('Settings'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 30,
                    'parent_id'    => 'translation',
                ],
                [
                    'id'           => 'settings',
                    'url'          => 'settings',
                    'title'        => t('Settings'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-gear',
                    'position'     => 900,
                ],
                [
                    'id'           => 'general',
                    'url'          => 'settings',
                    'title'        => t('General'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 10,
                    'parent_id'    => 'settings',
                ],
                [
                    'id'           => 'seo',
                    'url'          => 'seo',
                    'title'        => t('SEO'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 20,
                    'parent_id'    => 'settings',
                ],
                [
                    'id'           => 'site-health',
                    'url'          => 'site-health',
                    'title'        => t('Site Health'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 30,
                    'parent_id'    => 'settings',
                ],
                [
                    'id'           => 'site-storage',
                    'url'          => 'site-storage',
                    'title'        => t('Storage'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 40,
                    'parent_id'    => 'settings',
                ],
            ]
        ));
    }

    /**
     * Get the user menu items of the signed-in accounts of the browser: the current one first,
     * the others switch to on click. Each carries `account` and its own `sign_out` URL.
     *
     * @param int $position Position of the current account, the others follow it.
     * @return array<int, array<string, mixed>>
     */
    private static function accounts(int $position): array
    {
        $current = User::current();
        $items   = [];

        foreach ($current !== null ? [$current, ...Auth::getAccounts()] : Auth::getAccounts() as $i => $user) {
            $items[] = [
                'id'       => 'account-' . $user->identifier,
                'title'    => $user instanceof User && $user->showname !== '' ? $user->showname : $user->identifier,
                'icon'     => 'ph ph-user-circle',
                'account'  => $user->identifier,
                'current'  => $user === $current,
                'sign_out' => url('sign-out?account=' . rawurlencode($user->identifier)),
                'position' => $position + $i,
            ];
        }

        return $items;
    }
}
