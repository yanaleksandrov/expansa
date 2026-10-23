<?php
/**
 * Updates of the core, plugins, themes and translations: a summary with the status, then a section per accordion
 * item, like the site-health page; the data comes from App\Dashboard\Pages\Updates.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/updates.php
 *
 * @var string                                $checkedAt   Time of the last check, formatted.
 * @var App\Dashboard\Pages\Values\Release|null $core        Available release of the core, null when the installed one is the latest.
 * @var App\Dashboard\Pages\Values\Section[]    $sections    Core, plugins, themes and translations with updates, history, environment.
 * @var int                                   $count       Number of updates.
 * @var array<string, int>                    $types       Number of updates by type: `major`, `minor`, `patch`.
 * @var string                                $status      `critical` with a security fix, `recommended` with updates, `good` without.
 * @var string                                $open        Section open on load: the core or the first one with updates.
 * @var App\Dashboard\Pages\Values\Upgrade[]    $history     Past updates.
 * @var array<string, string>                 $environment Facts about the server by label.
 *
 * @package Expansa\Templates
 */

$meta = [
    'good'        => [
        'icon' => 'ph ph-check-circle',
        'text' => t('Everything is up to date'),
    ],
    'recommended' => [
        'icon' => 'ph ph-arrow-circle-up',
        'text' => t('Updates are available'),
    ],
    'critical'    => [
        'icon' => 'ph ph-shield-warning',
        'text' => t('A security fix is available'),
    ],
];

$kinds = [
    'major' => [
        'class' => 'badge--red-lt',
        'label' => t('Major'),
    ],
    'minor' => [
        'class' => 'badge--royal-lt',
        'label' => t('Minor'),
    ],
    'patch' => [
        'class' => 'badge--green-lt',
        'label' => t('Patch'),
    ],
];
?>
<div class="overview">
    <div class="overview-inner">
        <div class="overview-summary">
            <span class="overview-icon overview-icon--{{ $status }}"><i class="{{ $meta[$status]['icon'] }}"></i></span>
            <h4>{!! t('Updates') !!}</h4>
            <p class="t-muted">{!! $meta[$status]['text'] !!}</p>
            @if($count > 0)
                <div class="df jcc fww g-2">
                    @foreach($kinds as $type => $badge)
                        @if($types[$type] > 0)
                            <span class="badge {{ $badge['class'] }}">{!! $badge['label'] !!}: {{ $types[$type] }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
            <div class="updates-settings fs-13 t-muted">
                <span>{!! t('Last check: %s', $checkedAt) !!} · <a href="#">{!! t('Check Again') !!}</a></span>
                <label class="updates-toggle">
                    <input type="checkbox" checked> {!! t('Install security fixes automatically') !!}
                </label>
            </div>
            @if($count > 0)
                <button type="button" class="btn btn--primary"><i class="ph ph-download-simple"></i> {!! t('Update All') !!}</button>
            @endif
        </div>

        <div class="accordion card card-border overview-accordion" u-data="{open: '{{ $open }}'}">
            @foreach($sections as $section)
                <div class="accordion-item">
                    <button type="button" class="accordion-title overview-title" @click="open = open === '{{ $section->id }}' ? '' : '{{ $section->id }}'" :class="open === '{{ $section->id }}' && 'is-active'">
                        <i class="{{ $section->icon }} overview-title-icon"></i>
                        <span class="overview-title-text">{!! $section->title !!}</span>
                        @if($section->badge)
                            <span class="badge badge--sm badge--orange-lt">{{ $section->badge }}</span>
                        @endif
                        <i class="ph ph-caret-down overview-caret"></i>
                    </button>
                    <div class="accordion-panel overview-panel" u-collapse="open === '{{ $section->id }}'"<?php echo $section->id === $open ? '' : ' hidden'; ?>>
                        @if($section->id === 'core')
                            <ul class="overview-items">
                                <li class="overview-item updates-item">
                                    <i class="ph ph-arrow-circle-up overview-item-icon overview-item-icon--recommended"></i>
                                    <div class="overview-item-body">
                                        <span>{!! t('Version %s Is Available', $core->latest) !!}</span>
                                        <div class="fs-12 t-muted">{!! t('Installed: %s', $core->current) !!} · {!! t('Released %s', $core->date) !!} · {!! $core->size !!}</div>
                                    </div>
                                    <button type="button" class="btn btn--sm btn--primary">{!! t('Update Now') !!}</button>
                                </li>
                            </ul>
                            <div class="fs-12 t-muted px-5">{!! t("What's New") !!}</div>
                            <ul class="updates-notes">
                                @foreach($core->notes as $note)
                                    <li><i class="ph ph-check"></i> {!! $note !!}</li>
                                @endforeach
                            </ul>
                            <div class="overview-panel-foot fs-12 t-muted">
                                <span><i class="ph ph-shield-check"></i> {!! t('A backup of the files and the database is made before the update.') !!}</span>
                            </div>
                        @elseif($section->id === 'history')
                            <ul class="overview-items">
                                @foreach($history as $entry)
                                    <li class="overview-item">
                                        <i class="{{ $entry->succeeded ? 'ph ph-check-circle overview-item-icon--good' : 'ph ph-x-circle overview-item-icon--critical' }} overview-item-icon"></i>
                                        <div class="overview-item-body">
                                            <div class="df jcsb g-3">
                                                <span>{!! $entry->name !!} {!! $entry->from !!} → {!! $entry->to !!}</span>
                                                <span class="t-muted t-right">{!! $entry->succeeded ? $entry->date : t('%s, rolled back', $entry->date) !!}</span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @elseif($section->id === 'environment')
                            <dl class="updates-facts fs-13">
                                @foreach($environment as $label => $value)
                                    <dt class="t-muted">{!! $label !!}</dt>
                                    <dd>{!! $value !!}</dd>
                                @endforeach
                            </dl>
                        @else
                            <ul class="overview-items">
                                @foreach($section->items as $item)
                                    <li class="overview-item updates-item">
                                        <i class="{{ $item->security ? 'ph ph-shield-warning overview-item-icon--critical' : 'ph ph-arrow-circle-up overview-item-icon--recommended' }} overview-item-icon"></i>
                                        <div class="overview-item-body">
                                            <span>{!! $item->name !!}</span>
                                            <div class="fs-12 t-muted">{!! $item->note !!}</div>
                                        </div>
                                        <div class="updates-version fs-13">
                                            <span class="t-muted">{!! $item->current !!}</span>
                                            <i class="ph ph-arrow-right t-muted"></i>
                                            <span class="fw-500">{!! $item->latest !!}</span>
                                            <span class="badge badge--sm {{ $kinds[$item->type]['class'] }}">{!! $kinds[$item->type]['label'] !!}</span>
                                        </div>
                                        <button type="button" class="btn btn--sm btn--outline">{!! t('Update') !!}</button>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="overview-panel-foot">
                                <span class="fs-12 t-muted">{!! t(':count {update|updates}', count($section->items)) !!}</span>
                                <button type="button" class="btn btn--sm btn--outline">{!! t('Update All') !!}</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
