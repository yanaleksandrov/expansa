<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Media field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/media.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// the name is also the variable of u-data
$name       = Safe::id($field->name);
$attributes = $field->attributes;
$multiple   = Safe::bool($attributes['multiple'] ?? false);

$attributes['type'] = 'file';
unset($attributes['multiple']);
$attributes['multiple'] = $multiple;

// Uploading (or picking) a new file replaces the current one for a single-item field,
// and is appended - skipping anything already attached - for a "gallery"-style field.
$appendUploaded = $multiple
    ? "{$name} = [...{$name}, ...(posts || [])]"
    : "{$name} = (posts || []).slice(0, 1)";
$appendSelected = $multiple
    ? "{$name} = [...{$name}, ...items.filter(item => !{$name}.some(existing => existing.id === item.id))]"
    : "{$name} = items.slice(0, 1)";
?>
<div class="{{ $field->class ?: 'dg g-1' }}" u-data="{ {!! $name !!}: [] }">
    @if($field->label)
        <span class="{{ $field->label_class ?: 'df aic jcsb fw-500' }}"@if($field->required) data-required="*"@endif>
            {{ $field->label }}
            @if($field->tooltip)
                <i class="ph ph-info" u-tooltip.click.prevent="'{!! $field->tooltip !!}'"></i>
            @endif
        </span>
    @endif

    <div class="storage storage--field" u-show="{!! $name !!}.length">
        <div class="storage__item" u-each.lazy="(item, i) in {!! $name !!}">
            <input type="hidden" name="{!! $name !!}[]" :value="item.id">
            <img class="storage__image" u-show="item.url || item.icon" :src="item.sizes?.thumbnail?.url || item.url || item.icon || ''" width="200" height="200" alt>
            <span class="storage__remove" @click.stop.prevent="{!! $name !!} = {!! $name !!}.filter((removed, index) => index !== i)" title="{!! t('Remove') !!}">
                <i class="ph ph-x"></i>
            </span>
        </div>
    </div>

    <div class="df g-2">
        <label class="btn btn--outline dif aic g-1">
            <i class="ph ph-upload-simple"></i> {!! t('Upload') !!}
            <input{!! Arr::toHtmlAttributes($attributes) !!} hidden @change="$ajax.post('media/upload', $el.files).then(({posts}) => { {!! $appendUploaded !!}; $el.value = ''; })">
        </label>
        <button type="button" class="btn btn--outline dif aic g-1" @click="$dialog.open('tmpl-media-library', { ...mediaLibraryDialog, multiple: {!! $multiple ? 'true' : 'false' !!}, onSelect(items) { items = Array.isArray(items) ? items : (items ? [items] : []); {!! $appendSelected !!}; } })">
            <i class="ph ph-image"></i> {!! t('Choose from Library') !!}
        </button>
    </div>

    @if($field->description)
        <div class="fs-13 t-muted lh-xs">{!! $field->description !!}</div>
    @endif
</div>
