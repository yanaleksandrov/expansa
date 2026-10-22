<?php
/**
 * Progress: the label and the percentage above the bar.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-progress.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Progress $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="dg g-1 mw">
    <div class="df jcsb fs-12 t-muted">
        <span>{{ $value->label }}</span>
        <span>{{ $value->percent }}%</span>
    </div>
    <div class="{{ trim('progress ' . $value->class) }}" style="--expansa-progress: {{ $value->percent }}%"></div>
</div>
