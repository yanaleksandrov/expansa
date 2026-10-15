<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Slug field: the URL of an entry in one line, the slug edited in place; the `slug` provider
 * of youla-expansa.js makes it from the source field and normalizes it while typed. With the `parent`
 * option, the name of a field, and `bases`, the URLs by its values, the base follows that field.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/slug.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$base   = (string) ($field->options['base'] ?? rtrim(url(), '/') . '/');
$link   = (string) ($field->options['link'] ?? '');
$from   = (string) ($field->options['from'] ?? '');
$parent = (string) ($field->options['parent'] ?? '');
$bases  = (array) ($field->options['bases'] ?? []);
$synced = $from !== '' && (string) ($field->attributes['value'] ?? '') === '';
?>
<div class="{{ trim(($field->class ?: 'field') . ' slug') }}" u-data="slug" data-from="{{ $from }}" data-base="{{ $base }}" data-synced="{{ $synced ? '1' : '' }}"@if($parent) data-parent="{{ $parent }}" data-bases="{{ json_encode($bases, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}"@endif{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</div>
	@endif
	<label class="slug-line" u-bind="line">
		<i class="ph ph-link-simple"></i>
		<span class="slug-base" u-text="base">{{ $base }}</span>
		<input{!! $field->htmlAttributes !!} u-bind="input" spellcheck="false" autocomplete="off">
		<span class="slug-auto" u-bind="auto"@if(! $synced) hidden @endif>{{ t('from the title') }}</span>
		<button class="slug-action" type="button" u-bind="regenerate" hidden title="{{ t('Make from the title') }}" aria-label="{{ t('Make from the title') }}">
			<i class="ph ph-arrows-clockwise"></i>
		</button>
		<button class="slug-action ph ph-copy" type="button" u-bind="copy" title="{{ t('Copy link') }}" aria-label="{{ t('Copy link') }}"></button>
		@if($link)
			<a class="slug-action" href="{{ $link }}" :href="base + value" target="_blank" title="{{ t('Open') }}" aria-label="{{ t('Open') }}"><i class="ph ph-arrow-square-out"></i></a>
		@endif
		@if($field->tooltip)
			<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
		@endif
	</label>
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
