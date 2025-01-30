<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Comma-separated list of links
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/links.php
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
    <template x-for="(badge, index) in item.<?php echo $prop; ?>">
        <span class="badge badge--lg" :class="badge.class" x-text="badge.title"></span>
    </template>
</div>
