<?php
/**
 * Updates of the core, plugins, themes and translations.
 * A layout on sample data: no update source is connected yet, the buttons do nothing.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/updates.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$checkedAt = '02.10.2026, 14:20';

$core = [
    'current' => EX_VERSION,
    'latest'  => '2025.7',
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
            ['name' => t('Russian'), 'current' => '2025.6', 'latest' => '2025.7', 'type' => 'patch', 'note' => t('128 new strings')],
            ['name' => t('German'), 'current' => '2025.6', 'latest' => '2025.7', 'type' => 'patch', 'note' => t('96 new strings')],
        ],
    ],
];

$history = [
    ['name' => 'Expansa', 'from' => '2025.5', 'to' => '2025.6', 'date' => '14.08.2026', 'ok' => true],
    ['name' => 'SEO Toolkit', 'from' => '1.3.4', 'to' => '1.3.5', 'date' => '02.08.2026', 'ok' => true],
    ['name' => 'Forms', 'from' => '0.9.1', 'to' => '0.9.2', 'date' => '19.07.2026', 'ok' => false],
];

$environment = [
    t('PHP')      => PHP_VERSION,
    t('Database') => 'MySQL 8.0.36',
    t('Memory')   => ini_get('memory_limit'),
    t('Backups')  => t('Last: yesterday, 03:00'),
];

$types = [
    'major' => ['class' => 'badge--red-lt', 'label' => t('Major')],
    'minor' => ['class' => 'badge--royal-lt', 'label' => t('Minor')],
    'patch' => ['class' => 'badge--green-lt', 'label' => t('Patch')],
];

$count = 1 + array_sum(array_map(fn (array $group) => count($group['items']), $groups));

echo view('table/header', ['title' => t('Updates'), 'badge' => (string) $count]);
?>
<div class="updates mw py-5 px-6 md:p-5">
    <div class="updates-main">
        <section class="card card-border updates-core">
            <div class="updates-core-head">
                <span class="updates-icon updates-icon--primary"><i class="ph ph-rocket-launch"></i></span>
                <div class="updates-core-title">
                    <div class="fs-12 t-muted">{!! t('Expansa core') !!}</div>
                    <h5>{!! t('Version %s is available', $core['latest']) !!}</h5>
                    <div class="fs-13 t-muted">
                        {!! t('Installed: %s', $core['current']) !!} · {!! t('Released %s', $core['date']) !!} · {!! $core['size'] !!}
                    </div>
                </div>
                <div class="updates-core-actions">
                    <button type="button" class="btn btn--primary"><i class="ph ph-download-simple"></i> {!! t('Update now') !!}</button>
                </div>
            </div>
            <div class="updates-notes">
                <div class="fs-12 t-muted">{!! t("What's new") !!}</div>
                <ul class="updates-notes-list">
                    @foreach($core['notes'] as $note)
                        <li><i class="ph ph-check"></i> {!! $note !!}</li>
                    @endforeach
                </ul>
            </div>
            <div class="updates-core-foot fs-12 t-muted">
                <i class="ph ph-shield-check"></i>
                {!! t('A backup of the files and the database is made before the update.') !!}
            </div>
        </section>

        @foreach($groups as $group)
            <section class="card card-border">
                <div class="updates-group-head">
                    <h6><i class="{!! $group['icon'] !!}"></i> {!! $group['title'] !!} <span class="badge">{!! count($group['items']) !!}</span></h6>
                    <button type="button" class="btn btn--sm btn--outline">{!! t('Update all') !!}</button>
                </div>
                <ul class="updates-list">
                    @foreach($group['items'] as $item)
                        <li class="updates-item">
                            <div class="updates-item-name">
                                <div class="fw-500">{!! $item['name'] !!}</div>
                                <div class="fs-12 t-muted">{!! $item['note'] !!}</div>
                            </div>
                            <div class="updates-item-version fs-13">
                                <span class="t-muted">{!! $item['current'] !!}</span>
                                <i class="ph ph-arrow-right t-muted"></i>
                                <span class="fw-500">{!! $item['latest'] !!}</span>
                                <span class="badge badge--sm {!! $types[$item['type']]['class'] !!}">{!! $types[$item['type']]['label'] !!}</span>
                            </div>
                            <button type="button" class="btn btn--sm btn--outline">{!! t('Update') !!}</button>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <aside class="updates-side">
        <section class="card card-border updates-box">
            <div class="df aic jcsb g-2">
                <h6>{!! t('Update check') !!}</h6>
                <span class="badge badge--green-lt">{!! t('On') !!}</span>
            </div>
            <div class="fs-13 t-muted">{!! t('Last check: %s', $checkedAt) !!}</div>
            <button type="button" class="btn btn--outline"><i class="ph ph-arrows-clockwise"></i> {!! t('Check again') !!}</button>
            <label class="updates-toggle fs-13">
                <input type="checkbox" checked> {!! t('Install security fixes automatically') !!}
            </label>
        </section>

        <section class="card card-border updates-box">
            <h6>{!! t('Environment') !!}</h6>
            <dl class="updates-facts fs-13">
                @foreach($environment as $label => $value)
                    <dt class="t-muted">{!! $label !!}</dt>
                    <dd>{!! $value !!}</dd>
                @endforeach
            </dl>
        </section>

        <section class="card card-border updates-box">
            <h6>{!! t('History') !!}</h6>
            <ul class="updates-history fs-13">
                @foreach($history as $entry)
                    <li>
                        <i class="{!! $entry['ok'] ? 'ph ph-check-circle t-green' : 'ph ph-x-circle t-red' !!}"></i>
                        <div>
                            <div>{!! $entry['name'] !!} {!! $entry['from'] !!} → {!! $entry['to'] !!}</div>
                            <div class="fs-12 t-muted">{!! $entry['ok'] ? $entry['date'] : t('%s, rolled back', $entry['date']) !!}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    </aside>
</div>
