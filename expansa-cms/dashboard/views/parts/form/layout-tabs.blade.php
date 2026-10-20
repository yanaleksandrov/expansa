<?php

use Expansa\Builders\Form\Data;

/**
 * Form tabs: all tabs of a level, the menu and their panels.
 * The form holds them: `'class' => 'tab'` and `'u-data' => 'tab as tab'` in its attributes.
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<ul class="tab-nav" u-sticky>
	@foreach($field->tabs as $tab)
		<li class="tab-title{{ $tab->active ? ' is-active' : '' }}" u-bind="tab.button('{{ $tab->prop }}')"@if($tab->condition) u-show="{{ $tab->condition }}"@endif @if(! $tab->visible) hidden @endif>
			@if($tab->icon)
				<i class="{{ $tab->icon }}"></i>
			@endif
			{!! $tab->label !!}
		</li>
	@endforeach
</ul>
@foreach($field->tabs as $tab)
	<div class="tab-content" u-show="tab.active === '{{ $tab->prop }}'{{ $tab->condition ? ' && (' . $tab->condition . ')' : '' }}"@if(! $tab->active) hidden @endif>
		@if($tab->caption)
			<h5 class="tab-caption">{!! $tab->caption !!}</h5>
		@endif
		@if($tab->description)
			<div class="tab-description">{!! $tab->description !!}</div>
		@endif
		{!! $tab->content ?: t('Tab content is empty.') !!}
	</div>
@endforeach
