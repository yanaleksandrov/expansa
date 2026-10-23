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
<div class="{{ $field->class ?: 'field' }}@if($field->invalid) is-invalid@endif"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</div>
	@endif
	<label class="field-item">
		{!! $field->before !!}
		<input{!! $field->htmlAttributes !!}@if($field->counter !== '') u-counter@endif>
		{!! $field->after !!}
		@if($field->copy)
			<i class="ph ph-copy" title="{{ t('Copy') }}" @click="$copy($el.parentElement.querySelector('input').value)"></i>
		@endif
		@if($field->tooltip)
			<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
		@endif
	</label>
	@if($field->counter !== '')
		<div class="field-counter" aria-live="polite">{{ $field->counter }}</div>
	@endif
	@if($field->invalid)
		<div class="field-error">{!! $field->invalid !!}</div>
	@endif
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
