<?php
/**
 * New comment on a post, rendered inside mails/wrapper: to the author of the post when it is published,
 * to the owner of the site when it waits for moderation.
 *
 * @var string $postTitle Title of the post.
 * @var string $author    Name of the comment author.
 * @var string $content   Text of the comment.
 * @var string $link      Page of the comment in the dashboard.
 * @var bool   $pending   Whether the comment waits for moderation.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<td style="padding: 2rem; color: #3f536e; font-family: Arial, sans-serif; line-height: 1.6;">
	<h1 style="font-size: 22px; text-align: center;">{{ $pending ? t('A comment is waiting for moderation') : t('New comment') }}</h1>
	<p>{{ t(':author commented on «:title»:', $author, $postTitle) }}</p>
	<p style="padding: 12px 16px; background: #f4f5f7; border-radius: 6px; white-space: pre-wrap;">{{ $content }}</p>
	<p style="text-align: center;"><a href="{{ $link }}" style="display: inline-block; padding: 12px 24px; background: #0c66e4; color: #fff; text-decoration: none; border-radius: 6px;">{{ $pending ? t('Moderate') : t('Open Comment') }}</a></p>
</td>
