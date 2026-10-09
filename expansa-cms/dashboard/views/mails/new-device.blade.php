<?php
/**
 * Warning about a sign-in from a new device, rendered inside mails/wrapper.
 *
 * @var string $name      Recipient name.
 * @var string $device    Browser and system.
 * @var string $ip        Client IP.
 * @var string $time      Time of the sign-in.
 * @var string $method    How the user signed in: password, passkey, a provider.
 * @var string $secureUrl "This wasn't me" link.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<td style="padding: 2rem; color: #3f536e; font-family: Arial, sans-serif; line-height: 1.6;">
	<h1 style="font-size: 22px; text-align: center;">{!! t('New sign-in to your account') !!}</h1>
	<p>{!! t('Hello, :name', $name ?? '') !!}</p>
	<p>{!! t('Your Expansa account was signed in to from a device it has not been used on before.') !!}</p>
	<p>
		{!! t('Device: :device', $device ?? '') !!}<br>
		{!! t('IP address: :ip', $ip ?? '') !!}<br>
		{!! t('Time: :time', $time ?? '') !!}<br>
		{!! t('Signed in with: :method', $method ?? '') !!}
	</p>
	<p>{!! t('If it was you, no action is needed.') !!}</p>
	<p style="text-align: center;"><a href="{{ $secureUrl }}" style="display: inline-block; padding: 12px 24px; background: #c9372c; color: #fff; text-decoration: none; border-radius: 6px;">{!! t('This Was Not Me') !!}</a></p>
	<p>{!! t('The button signs your account out of every device and sends you a link to set a new password.') !!}</p>
</td>
