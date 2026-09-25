<?php

use Expansa\Facades\Hook;
use Expansa\Facades\Safe;

/**
 * Single image uploader.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/image.php
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

Hook::add('renderDashboardFooter', function () {
    echo view('dialogs/selfie-maker');
}, 5);
?>
<div class="<?php echo $class; ?>">
	<div class="df aife g-4">
		<div class="image" u-data="avatar">
			<input type="file" u-ref="input" u-bind="uploader" accept="image/*" id="image-<?php echo $name; ?>" hidden>
			<span class="image__close" u-bind="remover" title="<?php echo t( 'Remove image' ); ?>">
				<i class="ph ph-x"></i>
			</span>
			<div class="image__container">
				<label for="image-<?php echo $name; ?>">
					<span class="avatar avatar--xl" u-bind="picture">
						<span u-bind="initials"></span>
					</span>
				</label>
				<span class="image__action" @click="$dialog.open('take-selfie', {})" title="{{ t('You can take a selfie. Allow the browser to access the camera') }}"><i class="ph ph-webcam"></i></span>
			</div>
		</div>
		<div class="dg g-1 mw50x9">
			<?php if ( $label ) : ?>
				<div class="<?php echo $label_class; ?>"><?php echo $label; ?></div>
			<?php endif; ?>
			<div class="fs-13 t-muted lh-xs dg g-1">
				<div>
					<a @click.prevent="$refs.input.click()"><?php echo $instruction ?: t( 'Upload a photo' ); ?></a>
					<span><?php echo t( 'WEBP, PNG, JPG or GIF (max. 400×400px)' ); ?></span>
				</div>
				<a @click.prevent="$dialog.open('tmpl-media-library', { ...mediaLibraryDialog, multiple: false, type: 'image', onSelect(item) { if (item) { image = item.sizes?.thumbnail?.url || item.url; } } })"><?php echo t( 'Or choose from the media library' ); ?></a>
			</div>
		</div>
	</div>
	<input type="hidden" name="<?php echo $name; ?>" u-prop="image">
</div>
