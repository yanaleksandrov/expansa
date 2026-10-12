<?php

use Expansa\Builders\Form\Data;

/**
 * Form tab markup
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/tab.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

if ($field->content === '') {
    return;
}
?>
<!-- tab "{{ $field->name }}" start -->
<div class="tab__content {{ $field->class_content }}" u-bind="tabContent('{{ $field->name }}')" hidden>
	@if($field->caption || $field->description)
		<div class="dg mb-8">
			@if($field->caption)
				<h4>{!! $field->caption !!}</h4>
			@endif
			@if($field->description)
				<div class="t-muted fs-13 mt-1 mw-800">{!! $field->description !!}</div>
			@endif
		</div>
	@endif
	<div class="dg g-8">
		{!! $field->content !!}
	</div>
</div>
