<?php
/**
 * Updates of the core, plugins, themes and translations.
 *
 * The data comes from App\Dashboard\Pages\Updates.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/updates.php
 *
 * @var string               $checkedAt
 * @var array<string, mixed> $core        The current and the latest version with its notes.
 * @var array[]              $groups      Plugins, themes and translations with updates.
 * @var int                  $count       Number of updates.
 * @var array[]              $history
 * @var array<string, mixed> $environment
 *
 * @package Expansa\Templates
 */

$types = [
    'major' => ['class' => 'badge--red-lt', 'label' => t('Major')],
    'minor' => ['class' => 'badge--royal-lt', 'label' => t('Minor')],
    'patch' => ['class' => 'badge--green-lt', 'label' => t('Patch')],
];

echo view('components/table/header', ['title' => t('Updates'), 'badge' => (string) $count]);
?>
<div class="updates mw py-5 px-6 md:p-5">
    <div class="updates-main">
        <section class="card card-border updates-core">
            <div class="updates-core-head">
                <span class="updates-icon updates-icon--primary"><i class="ph ph-rocket-launch"></i></span>
                <div class="updates-core-title">
                    <div class="fs-12 t-muted">{!! t('Expansa Core') !!}</div>
                    <h5>{!! t('Version %s Is Available', $core['latest']) !!}</h5>
                    <div class="fs-13 t-muted">
                        {!! t('Installed: %s', $core['current']) !!} · {!! t('Released %s', $core['date']) !!} · {!! $core['size'] !!}
                    </div>
                </div>
                <div class="updates-core-actions">
                    <button type="button" class="btn btn--primary"><i class="ph ph-download-simple"></i> {!! t('Update Now') !!}</button>
                </div>
            </div>
            <div class="updates-notes">
                <div class="fs-12 t-muted">{!! t("What's New") !!}</div>
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
                    <button type="button" class="btn btn--sm btn--outline">{!! t('Update All') !!}</button>
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
                <h6>{!! t('Update Check') !!}</h6>
                <span class="badge badge--green-lt">{!! t('On') !!}</span>
            </div>
            <div class="fs-13 t-muted">{!! t('Last check: %s', $checkedAt) !!}</div>
            <button type="button" class="btn btn--outline"><i class="ph ph-arrows-clockwise"></i> {!! t('Check Again') !!}</button>
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
