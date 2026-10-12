<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Text input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/text.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="{{ $field->class ?: 'field' }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</div>
	@endif
	<label class="field-item">
		{!! $field->before !!}
		<input{!! $field->htmlAttributes !!}>
		{{ $field->after }}
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
