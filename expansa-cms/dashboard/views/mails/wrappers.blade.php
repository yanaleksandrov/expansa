<?php

use Expansa\Url;

/**
 * Expansa is installed information
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/mail/wrapper.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */

if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$template = trim( $__data['body_template'] ?? '' );
if ( empty( $template ) ) {
	return false;
}
?>
<table style="margin: 2rem auto 3rem; width: 90%; max-width: 540px; color: #151d26;">
	<tbody>
		<tr>
			<td style="text-align: center;">
				<img style="display: inline-block" src="<?php echo Url::site( 'dashboard/assets/images/logo-decorate.svg' ); ?>" width="212" height="124" alt="Expansa CMS">
			</td>
		</tr>
		<tr>
			<td style="background: #fff; border: 1px solid #e6e7e9; border-top: 4px solid #206bc4; margin-top: 20px; margin-bottom: 20px;">
				<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
					<tr>
						<td style="padding: 2rem; text-align: center; ">
							<template x-if="title">
								<h3 style="margin: 0;" x-text="title"></h3>
							</template>
							<template x-if="subtitle">
								<p style="color: #7e848b; max-width: 320px; margin: 10px auto 0;" x-text="subtitle"></p>
							</template>
						</td>
					</tr>
					<tr>
						<td>
							<p style="margin: 0; text-align: center; font-size: 10.35px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; color: #7c8188; height: 7px;">
								<span style="background-color: #fff; display: inline-block; padding: 0 8px;"><?php echo t( "What's next?" ); ?></span>
							</p>
						</td>
					</tr>
					<tr>
						<td>
							<hr style="border:none; height:1px; background-color: #e6e7e9;">
						</td>
					</tr>
					<tr>
						<td style="padding: 2rem;" x-html="content"></td>
					</tr>
					<template x-if="bottom">
						<tr>
							<td style="opacity: 0.75; font-size: 12px; text-align: center; padding: 0 2rem 2rem;" x-html="bottom"></td>
						</tr>
					</template>
				</table>
			</td>
		</tr>
		<template x-if="footer">
			<tr>
				<td style="opacity: 0.75; font-size: 11px; line-height: 145%; text-align: center; padding: 1rem 2rem 0;" x-html="footer"></td>
			</tr>
		</template>
	</tbody>
</table>
