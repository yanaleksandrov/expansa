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
     * Data of the `updates` page: the core, the groups of extensions with updates, the update history
     * and the environment they are installed into.
     *
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        $groups = [
            [
                'title' => t('Plugins'),
                'icon'  => 'ph ph-plug',
                'items' => [
                    ['name' => 'SEO Toolkit', 'current' => '1.3.5', 'latest' => '1.4.0', 'type' => 'minor', 'note' => t('Schema markup for products')],
                    ['name' => 'Query Monitor', 'current' => '2.0.1', 'latest' => '2.0.3', 'type' => 'patch', 'note' => t('Security fix')],
                    ['name' => 'Forms', 'current' => '0.9.2', 'latest' => '1.0.0', 'type' => 'major', 'note' => t('New form builder, check your forms after updating')],
                ],
            ],
            [
                'title' => t('Themes'),
                'icon'  => 'ph ph-paint-brush',
                'items' => [
                    ['name' => 'Horizon', 'current' => '3.1.0', 'latest' => '3.2.0', 'type' => 'minor', 'note' => t('Dark mode for the header')],
                ],
            ],
            [
                'title' => t('Translations'),
                'icon'  => 'ph ph-translate',
                'items' => [
                    ['name' => t('Russian'), 'current' => '2027.6', 'latest' => '2027.7', 'type' => 'patch', 'note' => t('128 new strings')],
                    ['name' => t('German'), 'current' => '2027.6', 'latest' => '2027.7', 'type' => 'patch', 'note' => t('96 new strings')],
                ],
            ],
        ];

        return [
            'checkedAt'   => '02.10.2026, 14:20',
            'core'        => [
                'current' => EX_VERSION,
                'latest'  => '2027.7',
                'date'    => '28.09.2026',
                'size'    => '4.8 MB',
                'notes'   => [
                    t('AI assistant: plugin generation with live progress in the chat'),
                    t('Passkeys: sign-in without a password on every device'),
                    t('Faster dashboard pages and fewer database queries'),
                    t('Fixed: the owner account got no role during installation'),
                ],
            ],
            'groups'      => $groups,
            'count'       => 1 + array_sum(array_map(fn (array $group) => count($group['items']), $groups)),
            'history'     => [
                ['name' => 'Expansa', 'from' => '2027.5', 'to' => '2027.6', 'date' => '14.08.2026', 'ok' => true],
                ['name' => 'SEO Toolkit', 'from' => '1.3.4', 'to' => '1.3.5', 'date' => '02.08.2026', 'ok' => true],
                ['name' => 'Forms', 'from' => '0.9.1', 'to' => '0.9.2', 'date' => '19.07.2026', 'ok' => false],
            ],
            'environment' => [
                'PHP'      => PHP_VERSION,
                t('Database') => 'MySQL 8.0.36',
                t('Memory')   => ini_get('memory_limit'),
                t('Backups')  => t('Last: yesterday, 03:00'),
            ],
        ];
    }
}
