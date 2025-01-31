<?php

use Expansa\Facades\Safe;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-plugin.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$class = Safe::class($__data['key'] ?? []);
$prop  = Safe::prop($__data['key'] ?? []);
?>
<div class="<?php echo $class; ?>">
    <div class="fs-15 lh-sm fw-500" x-text="item.title"></div>
    <div class="mt-1 t-muted" x-text="item.description"></div>
    <div class="df g-1 mt-1 t-muted">
        <span class="dib">by <a href="#" class="dib">WP Engine</a></span> ·
        <span class="dib"><a href="#" class="dib">Settings</a></span> ·
        <a href="#" class="dib t-red">Delete</a>
    </div>
</div>
