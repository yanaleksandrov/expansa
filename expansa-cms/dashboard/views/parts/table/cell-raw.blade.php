<?php
/**
 * Value as plain text.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-raw.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
{{ is_scalar($value) ? $value : '' }}
