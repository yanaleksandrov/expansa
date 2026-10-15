<?php

use Expansa\Builders\Form\Data;

/**
 * Form divider
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/divider.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="card-hr"{!! $field->htmlAttributes !!}>{!! $field->label !!}</div>
