<?php

use Expansa\Facades\Safe;

/**
 * Hidden input field
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/hidden.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<input<?php echo Safe::attributes($__data['attributes'] ?? []); ?>>
