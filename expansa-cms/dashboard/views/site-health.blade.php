<?php
/**
 * Site health: server, security, database, storage, extensions and AI checks, a section per accordion item.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/site-health.php
 *
 * @package Expansa\Templates
 */

use App\Support\SiteHealth;

defined('EX_PATH') || exit;

$groups = SiteHealth::groups();
$count  = SiteHealth::count($groups);
$status = $count[SiteHealth::CRITICAL] > 0 ? SiteHealth::CRITICAL : ($count[SiteHealth::RECOMMENDED] > 0 ? SiteHealth::RECOMMENDED : SiteHealth::GOOD);

$meta = [
    SiteHealth::GOOD        => ['icon' => 'ph ph-check-circle', 'badge' => 'badge--green-lt', 'label' => t('Good')],
    SiteHealth::RECOMMENDED => ['icon' => 'ph ph-warning-circle', 'badge' => 'badge--orange-lt', 'label' => t('Recommended')],
    SiteHealth::CRITICAL    => ['icon' => 'ph ph-x-circle', 'badge' => 'badge--red-lt', 'label' => t('Critical')],
];

$summary = [
    SiteHealth::GOOD        => t('Everything is fine'),
    SiteHealth::RECOMMENDED => t('A few things can be improved'),
    SiteHealth::CRITICAL    => t('Some problems need attention'),
];

// the first section with a problem is open, all of them are closed when everything is fine
$problem = array_find($groups, fn (array $group) => SiteHealth::status($group['checks']) !== SiteHealth::GOOD);
$open    = $problem['id'] ?? '';
?>
<div class="site-health">
    <div class="site-health-inner">
        <div class="site-health-summary">
            <span class="site-health-icon site-health-icon--{{ $status }}"><i class="{{ $meta[$status]['icon'] }}"></i></span>
            <h4>{!! t('Site health') !!}</h4>
            <p class="t-muted">{!! $summary[$status] !!}</p>
            <div class="df jcc fww g-2">
                @foreach($count as $key => $number)
                    <span class="badge {{ $meta[$key]['badge'] }}">{!! $meta[$key]['label'] !!}: {{ $number }}</span>
                @endforeach
            </div>
        </div>

        <div class="accordion card card-border site-health-accordion" u-data="{open: '{{ $open }}'}">
            @foreach($groups as $group)
                <?php $groupStatus = SiteHealth::status($group['checks']); ?>
                <div class="accordion-item">
                    <button type="button" class="accordion-title site-health-title" @click="open = open === '{{ $group['id'] }}' ? '' : '{{ $group['id'] }}'" :class="open === '{{ $group['id'] }}' && 'active'">
                        <i class="{{ $group['icon'] }} site-health-title-icon"></i>
                        <span class="site-health-title-text">{!! $group['title'] !!}</span>
                        <span class="badge badge--sm {{ $meta[$groupStatus]['badge'] }}">{!! $meta[$groupStatus]['label'] !!}</span>
                        <i class="ph ph-caret-down site-health-caret"></i>
                    </button>
                    <div class="accordion-panel site-health-panel" u-show="open === '{{ $group['id'] }}'" u-collapse hidden>
                        <ul class="site-health-checks">
                            @foreach($group['checks'] as $check)
                                <li class="site-health-check">
                                    <i class="{{ $meta[$check['status']]['icon'] }} site-health-check-icon site-health-check-icon--{{ $check['status'] }}"></i>
                                    <div class="site-health-check-body">
                                        <div class="df jcsb g-3">
                                            <span>{!! $check['label'] !!}</span>
                                            <span class="t-muted t-right">{!! $check['value'] !!}</span>
                                        </div>
                                        @if($check['hint'] !== '')
                                            <div class="fs-12 t-muted">{!! $check['hint'] !!}</div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
