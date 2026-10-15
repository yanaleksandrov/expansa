<?php
/**
 * Post editor page: the title, slug and content, the publishing options and details aside;
 * the data comes from App\Dashboard\Pages\Post, the fields from the `post-editor` form.
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
