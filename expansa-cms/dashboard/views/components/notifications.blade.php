<?php
/**
 * Notifications of `$notice` and the `notify` API fragments, printed once per page by App\Dashboard\Assets.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/notifications.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="notifications" u-data="notice" @mouseenter="pause()" @mouseleave="resume()">
	<div u-each="(item, index) in items" class="notifications-item" :class="item.classes()" :style="`--notice-scale: ${1 - (items.length - index - 1) * 0.005}`">
		<div class="notifications-wrapper">
			<i class="ph" :class="`ph-${item.type === 'info' ? 'bell-ringing' : item.type === 'error' ? 'siren' : item.type === 'success' ? 'check' : 'shield-warning'} t-${item.type === 'info' ? 'gray' : item.type === 'error' ? 'red' : item.type === 'success' ? 'green' : 'orange'}`"></i>
			<div class="notifications-text" u-text="item.message"></div>
			<button type="button" class="notifications-close" u-show="item.closable" @click="close(item.id)">
				<svg class="notifications-spinner" viewBox="0 0 24 24" width="24" height="24">
					<circle cx="12" cy="12" r="11" @load="$el.style.animationDuration = item.duration + 'ms'; $el.style.animationDelay = '-' + elapsed(item) + 'ms'"></circle>
				</svg>
			</button>
		</div>
	</div>
</div>
