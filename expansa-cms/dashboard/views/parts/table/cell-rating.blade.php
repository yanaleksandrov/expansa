<?php
/**
 * Rating out of five stars with the number of reviews.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-rating.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Rating $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<?php echo view('parts/rating', ['rating' => $value->value, 'reviews' => $value->reviews]); ?>
