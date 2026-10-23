<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Hook;

/**
 * Single image uploader.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/image.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

Hook::add('renderDashboardFooter', function () {
    echo view('parts/dialogs/selfie-maker');
}, 5);
?>
<div class="{{ $field->class ?: 'field' }}">
	<div class="df aife g-4">
		<div class="image" u-data="avatar">
			<input type="file" u-ref="input" u-bind="uploader" accept="image/*" id="image-{{ $field->name }}" hidden>
			<span class="image__close" u-bind="remover" title="{!! t('Remove image') !!}">
				<i class="ph ph-x"></i>
			</span>
			<div class="image__container">
				<label for="image-{{ $field->name }}">
					<span class="avatar avatar--xxl" u-bind="picture">
						<span u-bind="initials"></span>
					</span>
				</label>
				<span class="image__action" @click="$dialog.open('tmpl-selfie-maker')" title="{{ t('You can also take a selfie. Allow your browser to access the camera.') }}"><i class="ph ph-webcam"></i></span>
			</div>
		</div>
		<div class="dg g-1 mw50x9">
			@if($field->label)
				<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</div>
			@endif
			<div class="fs-13 t-muted lh-xs dg g-1">
				<div>
					<a @click.prevent="$refs.input.click()">{!! $field->instruction ?: t('Upload a photo') !!}</a>
					<span>{!! t('WEBP, PNG, JPG or GIF (max. 400×400px)') !!}</span>
				</div>
				<a class="btn btn--outline" @click.prevent="$dialog.open('tmpl-media-library', { multiple: false, type: 'image' }).then(item => { if (item) { image = item.sizes?.thumbnail?.url || item.url; } })">
					{!! t('Or choose from the media library') !!}
				</a>
			</div>
		</div>
	</div>
	<input type="hidden" name="{{ $field->name }}" u-prop="image">
</div>
