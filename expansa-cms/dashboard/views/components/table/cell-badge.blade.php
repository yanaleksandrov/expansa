<?php
/**
 * Value in a badge, nothing for an empty one.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-badge.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if (is_scalar($value) && $value !== '')
    <span class="badge">{{ $value }}</span>
@endif
