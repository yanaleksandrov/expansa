<?php

use Expansa\Builders\Form\Data;
use Expansa\Support\Arr;

/**
 * Submit button template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/submit.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$attributes = $field->attributes;
$attributes['type'] ??= 'submit';
?>
<div class="{{ $field->class ?: 'field' }}"{!! Arr::toHtmlAttributes($field->conditions) !!}>
    <button{!! Arr::toHtmlAttributes($attributes) !!}>{!! $field->label !!}</button>
</div>
