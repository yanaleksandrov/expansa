<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Publish date
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/date.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

[ $prop, $attributes ] = Safe::data(
	$__data ?? [],
	[
		'key'        => 'prop',
		'attributes' => 'array',
	]
)->values();
?>
<div<?php echo Arr::toHtmlAtts($attributes); ?>>
    Published
    <div x-text="item.<?php echo $prop; ?>"></div>
</div>
