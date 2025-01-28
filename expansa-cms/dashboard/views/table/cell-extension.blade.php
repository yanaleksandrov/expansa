<?php

use Expansa\View;
use Expansa\Safe;

/**
 * Table extension item.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-extension.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $title, $description, $screenshot, $author, $categories, $installed, $active, $installations, $date, $reviews, $rating, $expansa, $version ] = Safe::data(
    $__data ?? [],
	[
		'title'         => 'trim',
		'description'   => 'trim',
		'screenshot'    => 'url',
		'author'        => 'array',
		'categories'    => 'array',
		'installed'     => 'bool',
		'active'        => 'bool',
		'installations' => 'trim',
		'date'          => 'trim',
		'reviews'       => 'absint',
		'rating'        => 'float',
		'expansa'       => 'trim',
		'version'       => 'trim',
	]
)->values();
?>
<div class="plugins__item" x-data="<?php printf( '{installed: %s, active: %s}', $installed ? 'true' : 'false', $active ? 'true' : 'false' ); ?>">
	<div class="plugins__card">
		<div class="plugins__image" style="background-image: url(<?php echo $screenshot; ?>)"></div>
		<div class="plugins__data">
			<h4 class="plugins__title"><?php echo $title; ?></h4>
			<div class="plugins__description"><?php echo $description; ?></div>
			<div class="plugins__author">
				by <a href="#" target="_blank">Our Team</a>
			</div>
		</div>
		<div class="plugins__action">
			<button class="btn btn--outline"<?php ( $installed && $active ) && print( ' x-cloak' ); ?>><?php echo t( 'Install' ); ?></button>
			<button class="btn btn--primary"<?php ( $installed && ! $active ) && print( ' x-cloak' ); ?>><?php echo t( 'Activate' ); ?></button>
		</div>
	</div>
	<div class="plugins__info">
		<span class="plugins__text"><i class="ph ph-desktop-tower"></i> <?php echo $installations; ?></span>
		<span class="plugins__text"><i class="ph ph-calendar-dots"></i> <?php echo t( 'Last updated: :date', $date ); ?></span>
		<span class="plugins__text">
			<?php
			if ( $reviews > 0 ) :
				echo view(
					'global/rating',
					[
						'class'   => 'df aic g-1',
						'rating'  => $rating,
						'reviews' => $reviews,
					]
				);
			else :
				t( 'This plugin has not been rated yet' );
			endif;
			?>
		</span>
		<span class="plugins__text"><i class="ph ph-check"></i> <?php echo t( '**Compatible** with your Expansa version' ); ?></span>
	</div>
</div>