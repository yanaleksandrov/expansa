<?php

use Expansa\Facades\I18n;
use Expansa\Facades\Safe;

/**
 * Table raw text cell
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-toggle.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$key, $title, $description, $screenshot, $reviews, $version, $rating, $installed] = Safe::data(
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
	<div class="themes-image" style="background-image: url({{ $screenshot }})">
		<div class="themes-action">
			<button class="btn btn--outline" type="button">{{ t('View Demo') }}</button>
			<button class="btn btn--outline" type="button"<?php $installed && print( ' hidden' ); ?>>{{ t('Activate') }}</button>
			<button class="btn btn--primary" type="button"<?php ! $installed && print( ' hidden' ); ?>>{{ t('Customize') }}</button>
		</div>
	</div>
	<h6 class="themes-title">{{ $title }}</h6>
	@if ($description)
		<div class="themes-text">{{ $description }}</div>
	@endif
	<div class="themes-data">
		@if ($reviews > 0)
			<?php echo view('global/rating', ['rating' => $rating, 'reviews' => $reviews] ); ?>
		@else
			{{ t('This theme has not been rated yet') }}
		@endif

		@if ($version)
        	<div class="themes-text" title="{{ t('Version :number', $version) }}">{{ $version }}</div>
		@endif
    </div>
</div>
