<?php

use Expansa\Facades\Safe;

/**
 * Form divider
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/divider.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$label = Safe::trim($__data['label'] ?? '');
?>
<div class="card-hr">{!! $label !!}</div>
