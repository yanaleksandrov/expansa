<?php

/**
 * Rating out of five stars with the number of reviews.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/rating.php
 *
 * @var string $class
 * @var float  $rating
 * @var int    $reviews
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$class   ??= 'df aic g-1';
$rating  ??= 0.0;
$reviews ??= 0;
// a rating out of 0–5 would make str_repeat() fail
$full = (int) round(min(max($rating, 0), 5));
?>
<span class="{{ $class }}" title="{{ t(':rate rating based on :count reviews', $rating, $reviews) }}">
	{!! str_repeat('<i class="ph ph-star t-orange" aria-hidden="true"></i>', $full) !!}
	{!! str_repeat('<i class="ph ph-star t-muted" aria-hidden="true"></i>', 5 - $full) !!}
	<span class="t-dark ml-1">[{{ $reviews }}]</span>
</span>
