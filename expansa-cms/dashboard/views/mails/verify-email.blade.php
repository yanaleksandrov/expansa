<?php
/**
 * Email confirmation after signing up, rendered inside mails/wrapper.
 *
 * @var string $name      Recipient name.
 * @var string $verifyUrl Confirmation link.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<td style="padding: 2rem; color: #3f536e; font-family: Arial, sans-serif; line-height: 1.6;">
	<h1 style="font-size: 22px; text-align: center;">{!! t('Confirm your email') !!}</h1>
	<p>{!! t('Hello, :name!', $name ?? '') !!}</p>
	<p>{!! t('Open the link below to confirm the email of your new Expansa account. The link works for 24 hours.') !!}</p>
	<p style="text-align: center;"><a href="{{ $verifyUrl }}" style="display: inline-block; padding: 12px 24px; background: #0c66e4; color: #fff; text-decoration: none; border-radius: 6px;">{!! t('Confirm email') !!}</a></p>
	<p>{!! t('If you did not sign up, ignore this email: the account will not be activated.') !!}</p>
</td>
