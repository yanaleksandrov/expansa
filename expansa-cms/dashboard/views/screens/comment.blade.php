<?php
/**
 * Comment page: its text, status, author, a reply and details; the data comes from App\Dashboard\Pages\Comment.
 * The reply has its own form: its fields stand in the tab of the editor and point to it by `form`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/comment.blade.php
 *
 * @var App\Models\Comment $comment
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
{!! form('comment-editor') !!}
<form id="comment-reply" @submit.prevent="$ajax.post('comments/reply')">
    <input type="hidden" name="id" value="{{ $comment->id }}">
</form>
