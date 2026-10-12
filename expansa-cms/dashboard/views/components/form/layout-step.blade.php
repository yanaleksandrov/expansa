<?php

use Expansa\Builders\Form\Data;

/**
 * Form step
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/step.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<!-- step {{ $field->step }} -->
<div{!! $field->htmlAttributes !!}>
    {!! $field->content !!}
</div>

