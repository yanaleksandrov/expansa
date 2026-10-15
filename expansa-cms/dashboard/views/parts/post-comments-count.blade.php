<?php

use App\Models\Comment;

/**
 * Number of the comments of a post in the side panel of the post editor: the total of the comments
 * that are not deleted, then one badge per status with comments; shared with the API that changes them.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/post-comments-count.blade.php
 *
 * @var array<string, int> $counts Comments by status, see Comment::countByStatus().
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$total    = array_sum($counts);
$statuses = Comment::statuses();
$colors   = [
    Comment::STATUS_APPROVED => 't-green',
    Comment::STATUS_PENDING  => 't-amber',
    Comment::STATUS_SPAM     => 't-red',
    Comment::STATUS_REJECTED => 't-muted',
];
?>
<span class="post-editor-pill{{ $total === 0 ? ' is-empty' : '' }}">{{ $total }}</span>
@foreach ($statuses as $status => $label)
    @if (! empty($counts[$status]))
        <span class="badge {{ $colors[$status] ?? '' }}" title="{{ $label }}">{{ $label }} {{ $counts[$status] }}</span>
    @endif
@endforeach
