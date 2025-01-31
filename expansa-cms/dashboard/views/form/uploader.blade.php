<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Files uploader template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/uploader.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

[ $name, $label, $class, $label_class, $reset, $before, $after, $instruction, $tooltip, $copy, $conditions, $attributes, $max_size ] = Safe::data(
	$__data ?? [],
	[
		'name'        => 'name',
		'label'       => 'trim',
		'class'       => 'class:field',
		'label_class' => 'class:field-label',
		'reset'       => 'bool:false',
		'before'      => 'trim',
		'after'       => 'trim',
		'instruction' => 'trim',
		'tooltip'     => 'attribute',
		'copy'        => 'bool:false',
		'conditions'  => 'array',
		'attributes'  => 'array',
		// uploader
		'max_size'    => 'trim:' . ini_get( 'upload_max_filesize' ),
	]
)->values();
?>
<div class="uploader dg g-3">
	<label class="dg g-1">
		<?php if ( $label ) : ?>
			<span class="df aic jcsb fw-500"><?php echo $label; ?></span>
		<?php endif; ?>
		<span class="uploader__container">
			<?php if ( $instruction ) : ?>
				<span class="fw-500"><?php echo $instruction; ?></span>
			<?php endif; ?>
			<span class="fs-13 t-muted"><?php echo t( 'Maximum upload file size is :maxsize', $max_size ); ?></span>
		</span>
        <input type="file"<?php echo Arr::toHtmlAtts($attributes); ?>>
    </label>
</div>
