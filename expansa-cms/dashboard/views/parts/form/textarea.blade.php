<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Textarea field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/textarea.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$attributes = $field->attributes;
$value      = Safe::textarea($attributes['value'] ?? '');

$attributes['rows'] ??= 1;

unset($attributes['value']);
?>
<div class="{{ $field->class ?: 'field' }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<span class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</span>
	@endif
	<label class="field-item">
		{!! $field->before !!}
		<textarea{!! Arr::toHtmlAttributes($attributes) !!}>{!! $value !!}</textarea>
		{!! $field->after !!}
		@if($field->copy)
			<i class="ph ph-copy" title="{{ t('Copy') }}" @click="$copy({{ $field->prop }})"></i>
		@endif
		@if($field->tooltip)
			<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
		@endif
	</label>
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
