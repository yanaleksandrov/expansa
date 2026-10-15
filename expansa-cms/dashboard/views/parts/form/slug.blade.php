<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Slug field: the URL of an entry in one line, the slug edited in place; the `slug` provider
 * of youla-expansa.js makes it from the source field and normalizes it while typed.
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
$synced = $from !== '' && (string) ($field->attributes['value'] ?? '') === '';
?>
<div class="{{ trim(($field->class ?: 'field') . ' slug') }}" u-data="slug" data-from="{{ $from }}" data-base="{{ $base }}" data-synced="{{ $synced ? '1' : '' }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</div>
	@endif
	<label class="slug-line" u-bind="line">
		<i class="ph ph-link-simple"></i>
		<span class="slug-base">{{ $base }}</span>
		<input{!! $field->htmlAttributes !!} u-prop="value" u-bind="input" spellcheck="false" autocomplete="off">
		<span class="slug-auto" u-show="synced"@if(! $synced) hidden @endif>{{ t('from the title') }}</span>
		<button class="slug-action" type="button" u-show="! synced && generated() !== '' && value !== generated()" u-bind="regenerate" hidden title="{{ t('Make from the title') }}" aria-label="{{ t('Make from the title') }}">
			<i class="ph ph-arrows-clockwise"></i>
		</button>
		<button class="slug-action ph ph-copy" type="button" @click="$copy(base + value)" title="{{ t('Copy link') }}" aria-label="{{ t('Copy link') }}"></button>
		@if($link)
			<a class="slug-action" href="{{ $link }}" target="_blank" title="{{ t('Open') }}" aria-label="{{ t('Open') }}"><i class="ph ph-arrow-square-out"></i></a>
		@endif
		@if($field->tooltip)
			<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
		@endif
	</label>
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
