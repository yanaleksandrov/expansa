<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;

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
<?php $tab = Safe::prop($field->name); ?>
<div class="tab__content {{ $field->class_content }}" u-bind="tabContent('{{ $tab }}')"@if(! $field->active) hidden @endif>
	@if($field->caption || $field->description)
		<div class="dg">
			@if($field->caption)
				<h4>{!! $field->caption !!}</h4>
			@endif
			@if($field->description)
				<div class="t-muted fs-13 mt-1 mw-800">{!! $field->description !!}</div>
			@endif
		</div>
	@endif
	{!! $field->content !!}
</div>
