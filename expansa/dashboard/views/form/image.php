<?php

use app\View;
use Expansa\Hook;
use Expansa\I18n;
use Expansa\Safe;

/**
 * Single image uploader.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/image.php
 *
 * TODO: add crop imag
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = Safe::data(
	$args ?? [],
	[
		'name'        => 'name',
		'label'       => 'trim',
		'class'       => 'class:field',
		'label_class' => 'class:field-label',
		'reset'       => 'bool:false',
		'before'      => 'trim',
		'after'       => 'trim',
		'instruction' => 'trim',
		'tooltip'     => 'attribute',
		'copy'        => 'bool:false',
		'conditions'  => 'array',
		'attributes'  => 'array',
	]
)->values();

$prop = Safe::prop( $attributes['name'] ?? $name );

Hook::add( 'expansa_dashboard_footer', function() {
	View::print( 'views/dialogs/selfie-maker' );
} );
?>
<div class="<?php echo $class; ?>">
	<div class="df aife g-4">
		<div class="image" x-data="avatar, tabs = 'upload'" x-init="content = 'Yan Aleksandrov'">
			<input type="file" id="fileInputs" x-ref="input" @change="add($event, () => $dialog.open('crop-image'))" hidden>
			<span class="image__close" @click="remove" x-show="image" title="<?php I18n::t_attr( 'Remove image' ); ?>" x-cloak>
				<i class="ph ph-x"></i>
			</span>
			<div class="image__container">
				<label for="fileInputs">
					<span class="avatar avatar--xl" :style="image && `background-image: url(${image})`">
						<span x-text="getInitials(content)" x-show="!image"></span>
					</span>
				</label>
				<span class="image__action" @click="$dialog.open('take-selfie', takeSelfieDialog)" title="<?php I18n::t_attr( 'You can take a selfie. Allow the browser to access the camera' ); ?>"><i class="ph ph-webcam"></i></span>
			</div>
		</div>
		<div class="dg g-1 mw50x9">
			<?php if ( $label ) : ?>
				<div class="<?php echo $label_class; ?>"><?php echo $label; ?></div>
			<?php endif; ?>
			<div class="fs-13 t-muted lh-xs">
				<a @click.prevent="$refs.input.click()"><?php echo $instruction; ?></a> <span><?php I18n::t( 'WEBP, PNG, JPG or GIF (max. 400×400px)' ); ?></span>
			</div>
		</div>
	</div>
</div>
