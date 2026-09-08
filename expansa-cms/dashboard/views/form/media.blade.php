<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Media field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/media.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$type, $name, $label, $label_class, $class, $description, $attributes, $tooltip] = Safe::data(
    $__data ?? [],
    [
		'type'        => 'id:text',
		'name'        => 'attribute|id',
		'label'       => 'trim:field',
		'label_class' => 'class:df aic jcsb fw-500',
		'class'       => 'class:dg g-1',
		'description' => 'trim',
		'attributes'  => 'array',
		'tooltip'     => 'trim|attribute',
    ]
)->values();

$attributes['type'] = 'file';
?>
<div class="<?php echo $class; ?>" u-data="{<?php echo $name; ?>: []}">
	<div class="dg g-1" u-media>
		<?php if ( $label ) : ?>
			<span class="<?php echo $label_class; ?>"><?php Safe::html( $label ); ?></span>
		<?php endif; ?>
		<template u-for="(item, id) in <?php echo $name; ?>">
			<img class="" :src="item.url" u-init="console.log(item.url)" alt="" width="200" height="200">
		</template>
		<input<?php echo Arr::toHtmlAtts( $attributes ); ?>>
		<?php if ( $tooltip ) : ?>
			<i class="ph ph-info" u-tooltip.click.prevent="'<?php echo $tooltip; ?>'"></i>
		<?php endif; ?>
	</div>
	<?php if ( $description ) : ?>
		<div class="fs-13 t-muted lh-xs"><?php echo $description; ?></div>
	<?php endif; ?>
</div>
