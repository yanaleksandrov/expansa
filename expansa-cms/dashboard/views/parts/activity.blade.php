<?php
/**
 * Activity tab: the timeline of an entity, newest first, in the card of an editor or, with `card` off,
 * on its own, e.g. in the profile; the lines of an editor come from App\Support\Activity::timeline(),
 * an edit with what its fields were and became.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/activity.blade.php
 *
 * @var App\Support\Values\Line[] $lines
 * @var bool                      $card  Show the timeline in the card with the heading, true by default.
 * @var string                    $empty Text without lines.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$card  ??= true;
$empty ??= t('Save the item to start its history.');
?>
@if ($card)
<div class="post-editor-main">
    <div class="post-editor-card">
        <div class="post-editor-section-head">
            <h3>{{ t('Activity') }}</h3>
        </div>
@endif
        @if ($lines === [])
            <p class="post-activity-empty">{{ $empty }}</p>
        @else
            <ol class="post-activity">
                @foreach ($lines as $line)
                    <li>
                        <span class="post-activity-icon"><i class="{{ $line->icon }}"></i></span>
                        <span class="post-activity-text">
                            @if ($line->user)
                                <b>{{ $line->user }}</b>
                            @endif
                            {{ $line->text }}
                            @if ($line->note)
                                <span class="post-activity-note">{{ $line->note }}</span>
                            @endif
                            @foreach ($line->changes as $change)
                                <span class="post-activity-note post-activity-change">
                                    @if (count($line->changes) > 1)
                                        <em>{{ $change->field }}</em>
                                    @endif
                                    <del>{{ $change->from !== '' ? $change->from : '—' }}</del>
                                    <i class="ph ph-arrow-right"></i>
                                    <ins>{{ $change->to !== '' ? $change->to : '—' }}</ins>
                                </span>
                            @endforeach
                        </span>
                        @if ($line->at)
                            <time datetime="{{ $line->datetime }}">{{ $line->date }}</time>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
@if ($card)
    </div>
</div>
@endif
