<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Slug field: the URL of an entry in one line, the slug edited in place; the `slug` provider
 * of youla-expansa.js gets it from the server for the source field and for the typed text.
 * `from` is the source field, `base` the URL before the slug; with `parent`, the name of a field,
 * and `bases`, the URLs by its values, the base follows that field; `suffix` is the part after the slug.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/slug.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$base   = $field->base ?? rtrim(url(), '/') . '/';
$config = [
    'base'   => $base,
    'suffix' => $field->suffix,
    'from'   => $field->from,
    'parent' => $field->parent,
    'bases'  => (object) $field->bases,
    'synced' => $field->from && (string) ($field->attributes['value'] ?? '') === '',
];
?>
<div class="{{ trim(($field->class ?: 'field') . ' slug') }}" u-data="{ ...slug, ...{{ json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }} }"{!! Arr::toHtmlAttributes($field->conditions) !!}>
	@if($field->label)
		<div class="{{ $field->label_class ?: 'field-label' }}"@if($field->required) data-required="*"@endif>{!! $field->label !!}</div>
	@endif
	<label class="slug-line" u-bind="line">
		<i class="ph ph-link-simple"></i>
		<span class="slug-base" u-text="base">{{ $base }}</span>
		<input{!! $field->htmlAttributes !!} u-bind="input" spellcheck="false" autocomplete="off">
		@if($field->suffix)
			<span class="slug-base">{{ $field->suffix }}</span>
		@endif
		@if($field->tooltip)
			<i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
		@endif
	</label>
	@if($field->instruction)
		<div class="field-instruction">{!! $field->instruction !!}</div>
	@endif
</div>
