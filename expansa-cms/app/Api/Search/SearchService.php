<?php

declare(strict_types=1);

namespace App\Api\Search;

use App\Dashboard\Menus;
use App\Models\User;
use App\Post\Taxonomy;
use App\Post\Type;
use Expansa\Builders\Tree;
use Expansa\Builders\Tree\Item;
use Expansa\Facades\Access;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;

/**
 * Search of the dashboard: its pages from the menus the user sees, the items of the searchable post types
 * and the terms of the taxonomies the user may edit and, with `users_edit`, the users. Plugins add their
 * sections with the `dashboardSearch` filter.
 *
 * @package App\Api
 */
final class SearchService
{
    /**
     * Shorter text finds too much to be of use.
     */
    private const int MIN_LENGTH = 2;

    /**
     * Results of a section, the dropdown stays short.
     */
    private const int LIMIT = 5;

    /**
     * Menus searched for pages, the ones of the dashboard links; the bar holds counters, not titles.
     */
    private const array MENUS = ['dashboard-main-menu', 'dashboard-panel-menu', 'dashboard-user-menu'];

    /**
     * Find the results of a text, a section header (a link without URL) before the links of each section.
     * The `dashboardSearch` filter gets the sections by id, `['title' => ..., 'links' => [...]]` in the order
     * of output, the text and the user; an empty section is left out.
     *
     * @param string $query
     * @return array<int, array{url: string, text: string}>
     */
    public function find(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < self::MIN_LENGTH) {
            return [];
        }

        $user     = User::current();
        $sections = [
            'dashboard' => [
                'title' => t('Dashboard'),
                'links' => $this->pages($query),
            ],
            ...$this->posts($query, $user),
            ...$this->terms($query, $user),
            'users'     => [
                'title' => t('Users'),
                'links' => $user !== null && Access::allows($user, 'users_edit') ? $this->users($query) : [],
            ],
        ];

        $sections = (array) Hook::call('dashboardSearch', $sections, $query, $user);

        $links = [];
        foreach ($sections as $section) {
            if (is_array($section['links'] ?? null) && $section['links'] !== []) {
                $header = [
                    'url'  => '',
                    'text' => (string) ($section['title'] ?? ''),
                ];

                $links = [...$links, $header, ...array_slice($section['links'], 0, self::LIMIT)];
            }
        }

        return $links;
    }

    /**
     * Pages of the menus the user sees, whose title or the title of their parent has the text:
     * `Settings › SEO`. A page linked from several items is found once.
     *
     * @param string $query
     * @return array<int, array{url: string, text: string}>
     */
    private function pages(string $query): array
    {
        // the menus are attached only in the dashboard context, the search comes through the API
        Menus::register();

        // pages found by their own title come first, by the title of their parent only after them
        $own    = [];
        $nested = [];
        $visit  = function (array $items, string $parent) use (&$visit, &$own, &$nested, $query): void {
            foreach ($items as $item) {
                $title = self::plain($item->title);
                $url   = (string) $item->url;

                // a link outside the dashboard, like signing out, is an action, not a page to jump to
                if ($url !== '' && $title !== '' && ! str_contains($url, '://') && ! isset($own[$url])) {
                    $page = [
                        'url'  => url('dashboard/' . ltrim($url, '/')),
                        'text' => $parent === '' ? $title : "$parent › $title",
                    ];

                    if (mb_stripos($title, $query) !== false) {
                        $own[$url] = $page;
                        unset($nested[$url]);
                    } elseif ($parent !== '' && mb_stripos($parent, $query) !== false) {
                        $nested[$url] ??= $page;
                    }
                }

                $visit($item->children, $title);
            }
        };

        // the user menu has its own "Settings", of the profile
        foreach (self::MENUS as $menu) {
            $visit(Tree::get($menu), $menu === 'dashboard-user-menu' ? t('Profile') : '');
        }

        return [...array_values($own), ...array_values($nested)];
    }

    /**
     * Items of the searchable post types the user may edit by the title, the newest first: a section per type.
     *
     * @param string    $query
     * @param User|null $user
     * @return array<string, array{title: string, links: array<int, array{url: string, text: string}>}>
     */
    private function posts(string $query, ?User $user): array
    {
        $sections = [];
        foreach (Type::filter(['searchable' => true]) as $type) {
            $isAllowed = fn (string $capability) => $user !== null && Access::allows($user, $capability);
            if (! array_all($type->capabilities, $isAllowed)) {
                continue;
            }

            $where = [
                'title[~]' => $query,
                'ORDER'    => ['created_at' => 'DESC'],
                'LIMIT'    => self::LIMIT,
            ];
            if ($type->canTrash) {
                $where['deleted_at'] = null;
            }

            $sections["type-$type->key"] = [
                'title' => $type->labelNamePlural,
                'links' => array_map(fn (array $post) => [
                    'url'  => url('dashboard/post?type=' . $type->key . '&id=' . $post['id']),
                    'text' => (string) $post['title'],
                ], Db::select($type->table, ['id [Int]', 'title'], $where) ?? []),
            ];
        }

        return $sections;
    }

    /**
     * Terms of the taxonomies the user may change by the name: a section per taxonomy.
     *
     * @param string    $query
     * @param User|null $user
     * @return array<string, array{title: string, links: array<int, array{url: string, text: string}>}>
     */
    private function terms(string $query, ?User $user): array
    {
        $sections = [];
        foreach (Taxonomy::filter() as $taxonomy) {
            if (! $taxonomy->canManage($user)) {
                continue;
            }

            $terms = Db::select('terms', ['id [Int]', 'name'], [
                'taxonomy' => $taxonomy->key,
                'name[~]'  => $query,
                'ORDER'    => ['name' => 'ASC'],
                'LIMIT'    => self::LIMIT,
            ]) ?? [];

            $sections["taxonomy-$taxonomy->key"] = [
                'title' => $taxonomy->labelNamePlural,
                'links' => array_map(fn (array $term) => [
                    'url'  => url('dashboard/term?id=' . $term['id']),
                    'text' => (string) $term['name'],
                ], $terms),
            ];
        }

        return $sections;
    }

    /**
     * Users by the login, the shown name or the email, linked to their profile.
     *
     * @param string $query
     * @return array<int, array{url: string, text: string}>
     */
    private function users(string $query): array
    {
        $users = Db::select('users', ['id [Int]', 'login', 'showname', 'email'], [
            'OR'    => [
                'login[~]'    => $query,
                'showname[~]' => $query,
                'email[~]'    => $query,
            ],
            'ORDER' => ['id' => 'ASC'],
            'LIMIT' => self::LIMIT,
        ]) ?? [];

        return array_map(fn (array $user) => [
            'url'  => url('dashboard/profile?id=' . $user['id']),
            'text' => sprintf('%s (%s)', $user['showname'] ?: $user['login'], $user['email']),
        ], $users);
    }

    /**
     * Text of a menu title: t() gives HTML, the dropdown shows plain text.
     *
     * @param mixed $title
     * @return string
     */
    private static function plain(mixed $title): string
    {
        return is_string($title) ? trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
    }
}
