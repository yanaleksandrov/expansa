<?php
/**
 * Amount formatted in its currency, colored by the status.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-money.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Money $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<strong class="{{ $value->class }}">{{ $value->text }}</strong>
