<?php
/**
 * Post editor page: the title, slug and content, the publishing options and details aside;
 * the data comes from App\Dashboard\Pages\Post, the fields from the `post-editor` form.
 * The new comment has its own form: its fields stand in the Comments tab and point to it by `form`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/post.blade.php
 *
 * @var App\Models\Post $post
 * @var App\Post\Type   $type
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
{!! form('post-editor') !!}
@if ($post->id > 0 && $post->isDiscussionOpen() && (App\Models\User::current()?->can('manage_comments') ?? false))
    <form id="post-comment" @submit.prevent="$ajax.post('comments/add')">
        <input type="hidden" name="post-type" value="{{ $post->type }}">
        <input type="hidden" name="id" value="{{ $post->id }}">
    </form>
@endif
