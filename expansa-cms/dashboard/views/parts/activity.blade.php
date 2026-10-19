<?php


/**
 * Activity tab of an editor: the card with the timeline of an entity, newest first;
 * the lines come from App\Support\Activity::timeline(), an edit with what its fields were and became.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/activity.blade.php
 *
 * @var array<int, array{icon: string, user: string, text: string, note: string, changes?: array<int, array{field: string, from: string, to: string}>, at: string}> $lines
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

?>
<div class="post-editor-main">
    <div class="post-editor-card">
        <div class="post-editor-section-head">
            <h3>{{ t('Activity') }}</h3>
        </div>
        @if ($lines === [])
            <p class="post-activity-empty">{{ t('Save the item to start its history.') }}</p>
        @else
            <ol class="post-activity">
                @foreach ($lines as $line)
                    <li>
                        <span class="post-activity-icon"><i class="{{ $line['icon'] }}"></i></span>
                        <span class="post-activity-text">
                            @if ($line['user'] !== '')
                                <b>{{ $line['user'] }}</b>
                            @endif
                            {{ $line['text'] }}
                            @if ($line['note'] !== '')
                                <span class="post-activity-note">{{ $line['note'] }}</span>
                            @endif
                            @foreach ($line['changes'] ?? [] as $change)
                                <span class="post-activity-note post-activity-change">
                                    @if (count($line['changes']) > 1)
                                        <em>{{ $change['field'] }}</em>
                                    @endif
                                    <del>{{ $change['from'] !== '' ? $change['from'] : '—' }}</del>
                                    <i class="ph ph-arrow-right"></i>
                                    <ins>{{ $change['to'] !== '' ? $change['to'] : '—' }}</ins>
                                </span>
                            @endforeach
                        </span>
                        @if ($line['at'] !== '')
                            <time datetime="{{ date('c', strtotime($line['at'])) }}">{{ App\Support\Dates::format($line['at']) }}</time>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
