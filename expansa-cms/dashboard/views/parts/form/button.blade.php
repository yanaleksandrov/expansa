<?php

use Expansa\Builders\Form\Data;

/**
 * Button.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/button.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<button{!! $field->htmlAttributes !!}>{!! $field->label !!}</button>
