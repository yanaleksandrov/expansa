<?php
/**
 * Password change notification, rendered inside mails/wrapper.
 *
 * @var string $name    Recipient name.
 * @var string $siteUrl Site URL without a trailing slash.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<td style="padding: 2rem; color: #3f536e; font-family: Arial, sans-serif; line-height: 1.6;">
	<h1 style="font-size: 22px; text-align: center;">{!! t('Your password was changed') !!}</h1>
	<p>{!! t('Hello, :name', $name ?? '') !!}</p>
	<p>{!! t('The password for your Expansa account was changed. Other signed-in devices have been signed out.') !!}</p>
	<p>{!! t('If you made this change, no further action is needed.') !!}</p>
	<p>{!! t('If you did not make this change, open :siteUrl directly in your browser, request another password reset, and secure the email account linked to Expansa.', $siteUrl ?? '') !!}</p>
	<p>{!! t('Do not reply with your password or verification codes. Expansa will never ask you for them by email.') !!}</p>
</td>
