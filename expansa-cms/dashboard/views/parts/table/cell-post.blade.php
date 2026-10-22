<?php
/**
 * Post a comment answers: the title leading to its editor, the link to its page, at the comment when it
 * is approved, and the number of its approved comments; nothing without a post.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-post.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Post|null $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if ($value)
    <a href="{{ $value->edit }}" class="fw-500 t-dark">{{ $value->title }}</a>
    <div class="df aic g-2 fs-12 mt-1 t-muted">
        @if ($value->view)
            <a href="{{ $value->view }}" class="t-muted" target="_blank">{{ t('View') }}</a>
        @endif
        <span class="df aic g-1" title="{{ t('Approved comments') }}"><i class="ph ph-chat-circle"></i> {{ $value->comments }}</span>
    </div>
@endif
