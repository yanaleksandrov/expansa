<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Table title with actions cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/title.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

[$prop, $attributes] = Safe::data(
    $__data ?? [],
	[
		'key'        => 'prop',
		'attributes' => 'array',
	]
)->values();
?>
<div<?php echo Arr::toHtmlAtts($attributes); ?>>
    <div class="fs-14 lh-sm">
        <a href="#" class="fw-500 t-dark" x-text="item.<?php echo $prop; ?>" @click="$dialog.open('jb-add-item')"></a>
        <span class="t-muted">— Draft</span>
    </div>
    <div class="df aic g-2 mt-1 hover--show">
        <a href="#">View</a>
        <a href="#">Duplicate</a>
        <a class="t-red" href="#">Trash</a>
    </div>
</div>
