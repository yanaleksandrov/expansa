<?php
/**
 * Column title in the table head.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-head.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="{{ $cell->key }} df aic g-1">
    {!! $cell->title !!}
    @if ($cell->sortable)
        <i class="ph ph-sort-ascending"></i>
    @endif
</div>
