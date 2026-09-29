<?php
/**
 * Password reset email, rendered inside mails/wrapper.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/mails/reset-password.php
 *
 * @var string $name     Recipient name.
 * @var string $resetUrl Link to the new password form.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<td style="padding: 2rem; color: #3f536e; font-family: Arial, sans-serif; line-height: 1.6;">
	<h1 style="font-size: 22px; text-align: center;">{!! t('Reset your password') !!}</h1>
	<p>{!! t('Hello, :name!', $name ?? '') !!}</p>
	<p>{!! t('We received a request to reset the password for your account. Use the button below to choose a new one. The link expires in one hour and works only once.') !!}</p>
	<p style="text-align: center; margin: 2rem 0;">
		<a href="{{ $resetUrl ?? '' }}" style="display: inline-block; padding: 12px 20px; color: #fff; background: #206bc4; text-decoration: none; border-radius: 4px;">{!! t('Reset password') !!}</a>
	</p>
	<p style="font-size: 13px; color: #7e848b;">{!! t('If the button does not work, copy this link into your browser:') !!}<br>{{ $resetUrl ?? '' }}</p>
	<p>{!! t('If you did not request a password reset, ignore this message: your password stays the same.') !!}</p>
</td>
