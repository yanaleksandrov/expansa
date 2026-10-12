<?php

use Expansa\Builders\Form\Data;

/**
 * Form title.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/title.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<header class="{{ $field->class ?: 't-center' }}">
    @if($field->label)
        <h4>{!! $field->label !!}</h4>
    @endif
    @if($field->instruction)
        <p class="t-muted">{!! $field->instruction !!}</p>
    @endif
</header>
