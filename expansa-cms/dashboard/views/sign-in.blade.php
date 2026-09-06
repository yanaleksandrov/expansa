<?php
/**
 * User sing-in template can be overridden by copying it to themes/yourtheme/dashboard/views/sign-in.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<main class="mw-360" u-data>
	<a href="{{ url() }}" class="df jcc mb-4" target="_blank">
		<img src="{{ url('dashboard/assets/images/logo-grid.svg') }}" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php echo form('user-sign-in', EX_DASHBOARD . 'forms/user-sign-in.php'); ?>
	<div class="fs-13 t-center t-muted mt-3">
		{!! t("Don't have an account yet? [Sign Up](:signUpLink)", url('sign-up')) !!}
	</div>

	<!-- notifications start -->
	<template u-if="$store.notifications.length">
		<div class="notifications">
			<template u-for="(notification, i) in $store.notifications">
				<div class="notifications-item" :class="notification.class" :style="`--notice-scale: ${1 - ($store.notifications.length - i - 1) * 0.005}`">
					<div class="notifications-wrapper">
						<template u-if="notification.type">
							<div class="notifications-icon">
								<i class="ph ph-bell-ringing t-gray" u-show="notification.type === 'info'"></i>
								<i class="ph ph-siren t-red" u-show="notification.type === 'error'"></i>
								<i class="ph ph-check t-green" u-show="notification.type === 'success'"></i>
								<i class="ph ph-shield-warning t-orange" u-show="notification.type === 'warning'"></i>
							</div>
						</template>
						<div class="notifications-text" u-text="notification.message"></div>
						<div class="notifications-close" :style="notification.duration && `--notice-animation: ${notification.animation}`" @click="$notification.close(notification.id)"></div>
					</div>
				</div>
			</template>
		</div>
	</template>
</main>
