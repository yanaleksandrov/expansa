<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Password field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/password.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// the generator and the strength check write and read the value through u-prop
$prop = $field->attributes['u-prop'] ?? $field->prop;

$attributes = [
	...$field->attributes,
	'name'   => $field->name,
	'u-prop' => $prop,
	':type'  => "visible ? 'text' : 'password'",
	'@input' => $field->generator ? "check($prop)" : '',
];

$messages = [
	'lowercase' => t('%d lowercase letters'),
	'uppercase' => t('%d uppercase letters'),
	'special'   => t('%d special characters'),
	'length'    => t('%d characters minimum'),
	'digit'     => t('%d numbers'),
];

// a requirement is shown when it has a message and a positive count
$characters = array_filter(
	$field->characters,
	fn (mixed $count, int|string $character): bool => isset($messages[$character]) && $count > 0,
	ARRAY_FILTER_USE_BOTH,
);
?>
<div class="{{ $field->class ?: 'field' }}" u-data="password">
	<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>
		{!! $field->label !!}
		@if($field->generator)
			<div class="ml-auto fw-400 fs-13 t-muted" @click="{{ $prop }} = generate()">{{ t('Generate') }}</div>
		@endif
	</div>
	<div class="field-item">
		{!! $field->before !!}
		<input{!! Arr::toHtmlAttributes($attributes) !!}>
		{!! $field->after !!}
		@if($field->switcher)
			<i class="ph" :class="visible ? 'ph-eye-closed' : 'ph-eye'" @click="visible = !visible"></i>
		@endif
		@if($field->copy)
			<i class="ph ph-copy" title="{{ t('Copy') }}" @click="$copy($el.parentElement.querySelector('input').value)"></i>
		@endif
	</div>
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
	@if($field->indicator)
		<div class="dg g-1 gtc-5 mt-1">
			<i class="pt-1" :class="progress > 20 ? 'bg-red' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="progress > 40 ? 'bg-amber' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="progress > 60 ? 'bg-orange' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="progress > 80 ? 'bg-green' : 'bg-muted-lt'"></i>
			<i class="pt-1" :class="progress === 100 ? 'bg-green' : 'bg-muted-lt'"></i>
		</div>
	@endif
	@if($characters)
		<div class="dg g-2 gtc-2 t-muted fs-13 mt-3 lh-xs">
			@foreach($characters as $character => $count)
				<div class="df aifs g-2" :class="valid.{{ $character }} && 't-green'">
					<i class="ph" :class="valid.{{ $character }} ? 'ph-check' : 'ph-x'"></i> <span>{!! sprintf($messages[$character], $count) !!}</span>
				</div>
			@endforeach
		</div>
	@endif
</div>
