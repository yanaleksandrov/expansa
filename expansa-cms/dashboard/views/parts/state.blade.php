<?php

use Expansa\Support\Arr;

/**
 * State instead of content, e.g. an empty table: an icon, the title and the description, both HTML of t(),
 * and up to two buttons below: a link with a URL, otherwise a button with `@click` in its attributes.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/state.php
 *
 * @var string                                  $title
 * @var string                                  $description
 * @var string                                  $icon        Symbol of the states sprite, `empty-page` by default.
 * @var Expansa\Builders\Table\Values\Link[]    $buttons
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
			@foreach($buttons as $button)
				@if($button->url)
					<a class="btn btn--outline" href="{{ $button->url }}"{!! Arr::toHtmlAttributes($button->attributes) !!}>@if($button->icon)<i class="{{ $button->icon }}"></i> @endif{{ $button->title }}</a>
				@else
					<button class="btn btn--outline" type="button"{!! Arr::toHtmlAttributes($button->attributes) !!}>@if($button->icon)<i class="{{ $button->icon }}"></i> @endif{{ $button->title }}</button>
				@endif
			@endforeach
		</div>
	@endif
</div>
