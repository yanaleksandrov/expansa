<?php
/**
 * Sign-in link, rendered inside mails/wrapper.
 *
 * @var string $name      Recipient name.
 * @var string $signInUrl One-time sign-in link.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<td style="padding: 2rem; color: #3f536e; font-family: Arial, sans-serif; line-height: 1.6;">
	<h1 style="font-size: 22px; text-align: center;">{!! t('Your sign-in link') !!}</h1>
	<p>{!! t('Hello, :name!', $name ?? '') !!}</p>
	<p>{!! t('Open the link below to sign in to Expansa. It works once, for 15 minutes.') !!}</p>
	<p style="text-align: center;"><a href="{{ $signInUrl }}" style="display: inline-block; padding: 12px 24px; background: #0c66e4; color: #fff; text-decoration: none; border-radius: 6px;">{!! t('Sign in') !!}</a></p>
	<p>{!! t('If you did not ask for it, ignore this email: nobody can sign in without opening the link.') !!}</p>
</td>
