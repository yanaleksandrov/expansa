<?php

use Expansa\Builders\Form\Data;

/**
 * Files uploader template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/uploader.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="uploader dg g-3">
	<label class="dg g-1">
		@if($field->label)
			<span class="df aic jcsb fw-500">{!! $field->label !!}</span>
		@endif
		<span class="uploader__container">
			@if($field->instruction)
				<span class="fw-500">{!! $field->instruction !!}</span>
			@endif
			<span class="fs-13 t-muted">{!! t('Maximum upload file size is :maxsize.', $field->max_size ?: ini_get('upload_max_filesize')) !!}</span>
		</span>
        <input type="file"{!! $field->htmlAttributes !!}>
    </label>
</div>
