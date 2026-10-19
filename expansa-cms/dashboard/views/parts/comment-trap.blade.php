<?php

use App\Models\Comment;

/**
 * Traps of a comment form for bots, see CommentsService::submit(): a field hidden from people and screen
 * readers that a bot fills, and the signed time of the form that tells a form sent too soon. Put them inside
 * the form that posts to `comments/submit`; without the time the comment is refused.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/comment-trap.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<input type="hidden" name="{{ Comment::STAMP }}" value="{{ Comment::stamp() }}">
<div style="position: absolute; inset-inline-start: -9999px; width: 1px; height: 1px; overflow: hidden" aria-hidden="true">
    <label>
        {{ t('Website') }}
        <input type="text" name="{{ Comment::TRAP }}" value="" tabindex="-1" autocomplete="off">
    </label>
</div>
