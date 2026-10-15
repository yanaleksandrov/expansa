<?php

use Expansa\Builders\Form\Data;

/**
 * Hidden input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/hidden.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<input{!! $field->htmlAttributes !!}>
