<?php

use Expansa\View;
use Expansa\I18n;
use Expansa\Safe;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-toggle.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $key, $title, $description, $screenshot, $reviews, $version, $rating, $installed ] = Safe::data(
    $__data ?? [],
	[
		'key'         => 'class',
		'title'       => 'trim',
		'description' => 'trim',
		'screenshot'  => 'url',
		'reviews'     => 'absint',
		'version'     => 'trim',
		'rating'      => 'float',
		'installed'   => 'bool',
	]
)->values();
?>
<div class="themes-item">
	<div class="themes-image" style="background-image: url(<?php echo $screenshot; ?>)">
		<div class="themes-action">
			<button class="btn btn--outline" type="button"><?php echo t( 'View Demo' ); ?></button>
			<button class="btn btn--outline" type="button"<?php $installed && print( ' hidden' ); ?>><?php echo t( 'Activate' ); ?></button>
			<button class="btn btn--primary" type="button"<?php ! $installed && print( ' hidden' ); ?>><?php echo t( 'Customize' ); ?></button>
		</div>
	</div>
	<h6 class="themes-title"><?php echo $title, I18n::_c( $installed, ' <i class="badge badge--green-lt">Active</i>' ); ?></h6>
	<?php if ( $description ) : ?>
		<div class="themes-text"><?php echo $description; ?></div>
	<?php endif; ?>
	<div class="themes-data"><?php
		if ( $reviews > 0 ) {
			echo view(
				'global/rating',
				[
					'rating'  => $rating,
					'reviews' => $reviews,
				]
			);
		} else {
			t( 'This theme has not been rated yet' );
		}

		if ( $version ) :
			?>
			<div class="themes-text" title="<?php echo t( 'Version :number', $version ); ?>"><?php echo $version; ?></div>
		<?php endif; ?>
	</div>
</div>
