<?php

use Expansa\Facades\I18n;

/**
 * Activity tab of an editor: the card with the timeline of an entity, newest first;
 * the lines come from App\Support\Activity::timeline().
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/activity.blade.php
 *
 * @var array<int, array{icon: string, user: string, text: string, note: string, at: string}> $lines
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$date = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
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
                                <q>{{ $line['note'] }}</q>
                            @endif
                        </span>
                        @if ($line['at'] !== '')
                            <time datetime="{{ date('c', strtotime($line['at'])) }}">{{ $date->format(strtotime($line['at'])) }}</time>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
