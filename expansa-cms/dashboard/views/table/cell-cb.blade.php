<?php

use Expansa\Facades\Safe;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-cb.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$prop = Safe::prop($__data['key'] ?? '');
?>
<input type="checkbox" :name="`items[${i}]`" u-bind="switcher"/>
