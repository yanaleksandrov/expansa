<?php
use Expansa\Sanitizer;
use Expansa\Url;

/**
 * Different site states.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/global/state.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $class, $title, $description, $icon ] = (
	new Sanitizer(
		$args ?? [],
		[
			'class'       => 'class:dg jic m-auto t-center p-5 mw-320',
			'title'       => 'trim',
			'description' => 'trim',
			'icon'        => 'id:empty-page',
		]
	)
)->values();
?>
<div class="<?php echo $class; ?>">
	<?php if ( $icon ) : ?>
		<svg><use xlink:href="<?php echo Url::dashboard( '/assets/sprites/states.svg#' . $icon ); ?>"></use></svg>
		<?php
	endif;
	if ( $title ) :
		?>
		<h6 class="mt-4 mw"><?php echo $title; ?></h6>
		<?php
	endif;
	if ( $description ) :
		?>
		<p class="t-muted"><?php echo $description; ?></p>
	<?php endif; ?>
</div>
