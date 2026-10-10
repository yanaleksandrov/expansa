<?php
/**
 * Notice to the old address that the email of the account is being changed, rendered inside mails/wrapper.
 *
 * @var string $name  Recipient name.
 * @var string $email New address.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<td style="padding: 2rem; color: #3f536e; font-family: Arial, sans-serif; line-height: 1.6;">
	<h1 style="font-size: 22px; text-align: center;">{!! t('Your email is being changed') !!}</h1>
	<p>{!! t('Hello, :name', $name ?? '') !!}</p>
	<p>{!! t('A change of the email of your Expansa account to :email was requested. It takes effect when the link sent to the new address is opened.', $email ?? '') !!}</p>
	<p>{!! t('If you did not request it, sign in, sign out of other devices and change your password in the Security tab of your profile.') !!}</p>
</td>
