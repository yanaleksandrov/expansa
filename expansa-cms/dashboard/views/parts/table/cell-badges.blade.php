<?php

use Expansa\Support\Arr;

/**
 * Badges in a row, e.g. the categories of a post or the roles of a user; badges with an empty text are skipped.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-badges.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Badge[] $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="df fww g-1">
    @foreach ($value as $badge)
        @if ($badge->text !== '')
            <span class="{{ $badge->class }}"{!! Arr::toHtmlAttributes($badge->attributes) !!}><i class="{{ $badge->icon }}"></i>{{ $badge->text }}</span>
        @endif
    @endforeach
</div>
