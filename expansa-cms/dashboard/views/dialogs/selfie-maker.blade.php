<?php
/**
 * Template for output selfie maker.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/dialogs/selfie-maker.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>

<!-- selfie maker start -->
<template id="take-selfie">
	<div class="p-7 t-center" x-data="{second: '', showImg: ''}">
		<div x-init="$stream.start($refs)" style="position: relative; overflow: hidden;">
			<video x-ref="video" class="db mw" autoplay style="object-fit: cover; aspect-ratio: 4/3;"></video>
			<canvas x-ref="canvas" x-show="!showImg" style="border-radius: 20rem; width: 240px; height: 240px; position: absolute; margin: auto; inset: 0; box-shadow: 0 0 0 999px rgb(255 255 255 / 60%);"></canvas>
			<img x-ref="image" x-show="showImg" x-cloak alt="" src="/dashboard/assets/images/1x1.png" style="border-radius: 20rem; width: 240px; height: 240px; position: absolute; margin: auto; inset: 0; box-shadow: 0 0 0 999px rgb(255 255 255 / 98%);">
		</div>
		<div
			class="fs-48"
			x-show="second > 0"
			x-text="second"
			:style="second && 'position: fixed; top: 1rem; left: 0; right: 0; margin: 0 auto; transition: all 1s; animation: ticker 1s ease infinite;'"
		></div>

		<div x-show="!showImg">
			<h6><?php echo t( 'Center your face' ); ?></h6>
			<div class="fs-14 t-muted mt-2 pl-4 pr-4">
				<?php echo t( 'Align your face to the center of the selfie area and then take a photo' ); ?>
			</div>
			<div class="df jcsb mt-6 mw">
				<button type="button" class="btn btn--outline" @click="$dialog.close(), $stream.stop()"><?php echo t( 'Cancel' ); ?></button>
				<button class="btn btn--primary" @click="$countdown.start(3, () => second = $countdown.second, () => showImg = $stream.snapshot($refs))"><?php echo t( 'Take Selfie' ); ?></button>
			</div>
		</div>

		<div x-show="showImg">
			<h6><?php echo t( 'Check quality' ); ?></h6>
			<div class="fs-14 t-muted mt-2 pl-4 pr-4">
				<?php echo t( 'Make sure your face is not blurred or out of the frame before continuing' ); ?>
			</div>
			<div class="df jcsb mt-6 mw">
				<button class="btn btn--outline" type="button" @click="showImg = ''"><i class="ph ph-arrows-clockwise"></i> <?php echo t( 'Take a new' ); ?></button>
				<button class="btn btn--primary" type="button" @click="showImg = ''"><i class="ph ph-user-focus"></i> <?php echo t( 'Use this photo' ); ?></button>
			</div>
		</div>
	</div>
</template>
