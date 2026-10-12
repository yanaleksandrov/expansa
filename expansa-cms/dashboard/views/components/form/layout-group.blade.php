<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Group
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/group.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$name, $label, $class, $labelClass, $contentClass, $content, $conditions] = Safe::data(
    $__data ?? [],
    [
        'name'          => 'name',
		'label'         => 'trim',
        'class'         => 'class:dg g-6 gtc-5 sm:gtc-1',
		'label_class'   => 'class:ga-1 fw-500',
        'content_class' => 'class:dg ga-4 g-6 gtc-2 sm:gtc-1',
		'content'       => 'trim',
		'conditions'    => 'array',
    ]
)->values();
?>
<div class="<?php echo $class; ?>"<?php echo Arr::toHtmlAttributes( $conditions ); ?>>
	@if($label)
		<div class="{{ $labelClass }}">{{ $label }}</div>
	@endif
	<div class="{{ $contentClass }}">
		{!! $content !!}
	</div>
</div>
