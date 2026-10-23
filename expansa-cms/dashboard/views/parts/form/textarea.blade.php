<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Textarea field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/textarea.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$attributes = $field->attributes;
$value      = (string) ($attributes['value'] ?? '');

unset($attributes['value']);
?>
<div class="{{ $field->class ?: 'field' }}@if($field->invalid) is-invalid@endif"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<span class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</span>
	@endif
	<label class="field-item">
		{!! $field->before !!}
		<textarea{!! Arr::toHtmlAttributes($attributes) !!}@if($field->counter !== '') u-counter@endif>{{ $value }}</textarea>
		{!! $field->after !!}
		@if($field->copy)
			<i class="ph ph-copy" title="{{ t('Copy') }}" @click="$copy($el.parentElement.querySelector('textarea').value)"></i>
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
