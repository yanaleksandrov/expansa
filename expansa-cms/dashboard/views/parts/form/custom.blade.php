<?php

use Expansa\Builders\Form\Data;

/**
 * Custom field markup.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/custom.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if(is_callable($field->callback))
	@php
		call_user_func($field->callback);
	@endphp
@elseif(is_string($field->callback))
	{!! $field->callback !!}
@endif
