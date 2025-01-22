<?php
use Expansa\Safe;

/**
 * Group
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/group.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $name, $label, $class, $labelClass, $contentClass, $content ] = Safe::data(
	$__data ?? [],
    [
        'name'          => 'name',
		'label'         => 'trim',
        'class'         => 'class:dg g-7 gtc-5 sm:gtc-1',
		'label_class'   => 'class:ga-1 fw-500',
        'content_class' => 'class:dg ga-4 g-7 gtc-2 sm:gtc-1',
		'content'       => 'trim',
    ]
)->values();
?>
<div class="<?php echo $class; ?>">
	@if($label)
		<div class="{{ $labelClass }}">{{ $label }}</div>
	@endif
	<div class="{{ $contentClass }}">
		{!! $content !!}
	</div>
</div>
