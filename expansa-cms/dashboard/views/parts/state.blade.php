<?php

/**
 * State instead of content, e.g. an empty table: an icon, the title and the description, both HTML of t(),
 * and up to two buttons below: `['label' => t('Add'), 'url' => ...]` or with a `click` expression instead
 * of the URL; the first one is the primary.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/state.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$title       ??= '';
$description ??= '';
$icon        = ($icon ?? '') ?: 'empty-page';
$buttons     = array_slice($buttons ?? [], 0, 2);
?>
<div class="state">
	<svg><use xlink:href="{{ url('/dashboard/assets/sprites/states.svg#' . $icon) }}"></use></svg>
	@if($title)
		<h6 class="state-title">{!! $title !!}</h6>
	@endif
	@if($description)
		<p class="state-description">{!! $description !!}</p>
	@endif
	@if($buttons)
		<div class="state-buttons">
			@foreach($buttons as $i => $button)
				@if(! empty($button['url']))
					<a class="btn btn--outline" href="{{ $button['url'] }}">{!! $button['label'] ?? '' !!}</a>
				@else
					<button class="btn btn--outline" type="button" @click="{{ $button['click'] ?? '' }}">{!! $button['label'] ?? '' !!}</button>
				@endif
			@endforeach
		</div>
	@endif
</div>
