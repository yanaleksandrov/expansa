<?php
/**
 * Site health: server, security, database, storage, extensions and AI checks, a section per accordion item;
 * the data comes from App\Dashboard\Pages\SiteHealth.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/site-health.php
 *
 * @var array[]            $groups  Sections with their `checks` and `status`.
 * @var array<string, int> $count   Number of checks by status.
 * @var string             $status  Status of the whole site: `good`, `recommended` or `critical`.
 * @var string             $open    Section open on load: the first one with a problem.
 * @var int                $checked Time of the network checks, kept for an hour; ?recheck runs them again.
 *
 * @package Expansa\Templates
 */

$meta = [
    'good'        => ['icon' => 'ph ph-check-circle', 'badge' => 'badge--green-lt', 'label' => t('Good')],
    'recommended' => ['icon' => 'ph ph-warning-circle', 'badge' => 'badge--orange-lt', 'label' => t('Recommended')],
    'critical'    => ['icon' => 'ph ph-x-circle', 'badge' => 'badge--red-lt', 'label' => t('Critical')],
];

$summary = [
    'good'        => t('Everything is fine'),
    'recommended' => t('A few things can be improved'),
    'critical'    => t('Some problems need attention'),
];
?>
<div class="overview">
    <div class="overview-inner">
        <div class="overview-summary">
            <span class="overview-icon overview-icon--{{ $status }}"><i class="{{ $meta[$status]['icon'] }}"></i></span>
            <h4>{!! t('Site Health') !!}</h4>
            <p class="t-muted">{!! $summary[$status] !!}</p>
            <div class="df jcc fww g-2">
                @foreach($count as $key => $number)
                    <span class="badge {{ $meta[$key]['badge'] }}">{!! $meta[$key]['label'] !!}: {{ $number }}</span>
                @endforeach
            </div>
            <p class="t-muted fs-13">
                {!! t('Network checks: %s', App\Support\Dates::format($checked)) !!} · <a href="?recheck">{!! t('Check Again') !!}</a>
            </p>
        </div>

        <div class="accordion card card-border overview-accordion" u-data="{open: '{{ $open }}'}">
            @foreach($groups as $group)
                <?php $groupStatus = $group['status']; ?>
                <div class="accordion-item">
                    <button type="button" class="accordion-title overview-title" @click="open = open === '{{ $group['id'] }}' ? '' : '{{ $group['id'] }}'" :class="open === '{{ $group['id'] }}' && 'is-active'">
                        <i class="{{ $group['icon'] }} overview-title-icon"></i>
                        <span class="overview-title-text">{!! $group['title'] !!}</span>
                        <span class="badge badge--sm {{ $meta[$groupStatus]['badge'] }}">{!! $meta[$groupStatus]['label'] !!}</span>
                        <i class="ph ph-caret-down overview-caret"></i>
                    </button>
                    <div class="accordion-panel overview-panel" u-collapse="open === '{{ $group['id'] }}'"<?php echo $group['id'] === $open ? '' : ' hidden'; ?>>
                        <ul class="overview-items">
                            @foreach($group['checks'] as $check)
                                <li class="overview-item">
                                    <i class="{{ $meta[$check['status']]['icon'] }} overview-item-icon overview-item-icon--{{ $check['status'] }}"></i>
                                    <div class="overview-item-body">
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
