<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

/**
 * Updates of the core, plugins, themes and translations.
 * Sample data: no update source is connected yet, the buttons of the page do nothing.
 *
 * @package App\Dashboard
 */
final class Updates
{
    /**
     * Data of the `updates` page: the core, the sections of extensions with updates, the number of updates
     * by type, the status of the site, the section open on load, the update history and the environment.
     * The status is `critical` with a security fix, `recommended` with any other update, `good` without them.
     *
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        $core = [
            'current' => EX_VERSION,
            'latest'  => '2027.7',
            'type'    => 'minor',
            'date'    => '28.09.2026',
            'size'    => '4.8 MB',
            'notes'   => [
                t('AI assistant: plugin generation with live progress in the chat'),
                t('Passkeys: sign-in without a password on every device'),
                t('Faster dashboard pages and fewer database queries'),
                t('Fixed: the owner account got no role during installation'),
            ],
        ];

        $groups = [
            [
                'id'    => 'plugins',
                'title' => t('Plugins'),
                'icon'  => 'ph ph-plug',
                'items' => [
                    [
                        'name'     => 'SEO Toolkit',
                        'current'  => '1.3.5',
                        'latest'   => '1.4.0',
                        'type'     => 'minor',
                        'security' => false,
                        'note'     => t('Schema markup for products'),
                    ],
                    [
                        'name'     => 'Query Monitor',
                        'current'  => '2.0.1',
                        'latest'   => '2.0.3',
                        'type'     => 'patch',
                        'security' => true,
                        'note'     => t('Security fix'),
                    ],
                    [
                        'name'     => 'Forms',
                        'current'  => '0.9.2',
                        'latest'   => '1.0.0',
                        'type'     => 'major',
                        'security' => false,
                        'note'     => t('New form builder, check your forms after updating'),
                    ],
                ],
            ],
            [
                'id'    => 'themes',
                'title' => t('Themes'),
                'icon'  => 'ph ph-paint-brush',
                'items' => [
                    [
                        'name'     => 'Horizon',
                        'current'  => '3.1.0',
                        'latest'   => '3.2.0',
                        'type'     => 'minor',
                        'security' => false,
                        'note'     => t('Dark mode for the header'),
                    ],
                ],
            ],
            [
                'id'    => 'translations',
                'title' => t('Translations'),
                'icon'  => 'ph ph-translate',
                'items' => [
                    [
                        'name'     => t('Russian'),
                        'current'  => '2027.6',
                        'latest'   => '2027.7',
                        'type'     => 'patch',
                        'security' => false,
                        'note'     => t('128 new strings'),
                    ],
                    [
                        'name'     => t('German'),
                        'current'  => '2027.6',
                        'latest'   => '2027.7',
                        'type'     => 'patch',
                        'security' => false,
                        'note'     => t('96 new strings'),
                    ],
                ],
            ],
        ];

        $groups = array_values(array_filter($groups, fn (array $group) => $group['items'] !== []));
        $items  = array_merge(...array_column($groups, 'items'));
        $isCore = version_compare($core['latest'], $core['current'], '>');
        $types  = array_count_values([...array_column($items, 'type'), ...($isCore ? [$core['type']] : [])]);
        $count  = array_sum($types);

        return [
            'checkedAt'   => '02.10.2026, 14:20',
            'core'        => $isCore ? $core : null,
            'groups'      => $groups,
            'count'       => $count,
            'types'       => [
                'major' => $types['major'] ?? 0,
                'minor' => $types['minor'] ?? 0,
                'patch' => $types['patch'] ?? 0,
            ],
            'status'      => match (true) {
                array_any($items, fn (array $item) => $item['security']) => 'critical',
                $count > 0                                               => 'recommended',
                default                                                  => 'good',
            },
            'open'        => $isCore ? 'core' : ($groups[0]['id'] ?? ''),
            'history'     => [
                [
                    'name' => 'Expansa',
                    'from' => '2027.5',
                    'to'   => '2027.6',
                    'date' => '14.08.2026',
                    'ok'   => true,
                ],
                [
                    'name' => 'SEO Toolkit',
                    'from' => '1.3.4',
                    'to'   => '1.3.5',
                    'date' => '02.08.2026',
                    'ok'   => true,
                ],
                [
                    'name' => 'Forms',
                    'from' => '0.9.1',
                    'to'   => '0.9.2',
                    'date' => '19.07.2026',
                    'ok'   => false,
                ],
            ],
            'environment' => [
                'PHP'         => PHP_VERSION,
                t('Database') => 'MySQL 8.0.36',
                t('Memory')   => ini_get('memory_limit'),
                t('Backups')  => t('Last: yesterday, 03:00'),
            ],
        ];
    }
}
