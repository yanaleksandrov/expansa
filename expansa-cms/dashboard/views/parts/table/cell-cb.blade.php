<?php
/**
 * Checkbox selecting the row for the bulk actions, the value is the row id.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-cb.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var int|string                  $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<input type="checkbox" name="items[]" value="{{ $value }}" u-bind="item">
