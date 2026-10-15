<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Group
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/group.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="{{ $field->class ?: 'dg g-6 gtc-5 sm:gtc-1' }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'ga-1 fw-500' }}">{{ $field->label }}</div>
	@endif
	<div class="{{ $field->content_class ?: 'dg ga-4 g-6 gtc-2 sm:gtc-1' }}">
		{!! $field->content !!}
	</div>
</div>
