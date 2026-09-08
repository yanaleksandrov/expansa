<?php

use Expansa\Facades\Hook;
use Expansa\Facades\Safe;

/**
 * Single image uploader.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/image.php
 *
 * TODO: add crop imag
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes ] = Safe::data(
	$__data ?? [],
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

$prop = Safe::prop($attributes['name'] ?? $name);

Hook::add('renderDashboardFooter', function () {
    echo view('dialogs/selfie-maker');
}, 5);
?>
<div class="<?php echo $class; ?>">
	<div class="df aife g-4">
		<div class="image" u-data="avatar, tabs = 'upload'" u-init="content = 'Yan Aleksandrov'">
			<input type="file" id="fileInputs" u-ref="input" @change="add($event, () => $dialog.open('crop-image'))" hidden>
			<span class="image__close" @click="remove" u-show="image" title="{{ t('Remove image') }}" hidden>
				<i class="ph ph-x"></i>
			</span>
			<div class="image__container">
				<label for="fileInputs">
					<span class="avatar avatar--xl" :style="image && `background-image: url(${image})`">
						<span u-text="getInitials(content)" u-show="!image"></span>
					</span>
				</label>
				<span class="image__action" @click="$dialog.open('take-selfie', takeSelfieDialog)" title="{{ t('You can take a selfie. Allow the browser to access the camera') }}"><i class="ph ph-webcam"></i></span>
			</div>
		</div>
		<div class="dg g-1 mw50x9">
			<?php if ( $label ) : ?>
				<div class="<?php echo $label_class; ?>"><?php echo $label; ?></div>
			<?php endif; ?>
			<div class="fs-13 t-muted lh-xs">
				<a @click.prevent="$refs.input.click()"><?php echo $instruction; ?></a> <span><?php echo t( 'WEBP, PNG, JPG or GIF (max. 400×400px)' ); ?></span>
			</div>
		</div>
	</div>
</div>
