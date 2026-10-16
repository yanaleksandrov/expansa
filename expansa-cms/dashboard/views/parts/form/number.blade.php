<?php

use Expansa\Builders\Form\Data;

/**
 * Input field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/number.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="{{ $field->class ?: 'field field--number' }}">
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</div>
	@endif
	<div class="field-item">
		{!! $field->before !!}
		<i class="ph ph-minus" @click="$spin(-1)"></i>
		<i class="ph ph-plus" @click="$spin(1)"></i>
		<input{!! $field->htmlAttributes !!} @keydown.e.prevent>
		{!! $field->after !!}
	</div>
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
