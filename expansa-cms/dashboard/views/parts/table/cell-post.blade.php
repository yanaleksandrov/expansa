<?php
/**
 * Post a comment answers: the title leading to its editor, the link to its page and the number of
 * its approved comments; `title`, `edit`, `view` and `count` of the value, nothing without a post.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-post.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$post = is_array($value) ? $value : [];
?>
@if ($post)
    <a href="{{ $post['edit'] }}" class="fw-500 t-dark">{{ $post['title'] }}</a>
    <div class="df aic g-2 fs-12 mt-1 t-muted">
        @if ($post['view'])
            <a href="{{ $post['view'] }}" class="t-muted" target="_blank">{{ t('View') }}</a>
        @endif
        <span class="df aic g-1" title="{{ t('Approved comments') }}"><i class="ph ph-chat-circle"></i> {{ (int) $post['count'] }}</span>
    </div>
@endif
