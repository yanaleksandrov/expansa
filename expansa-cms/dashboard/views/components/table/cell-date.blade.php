<?php
/**
 * Date with the status of the item above it, e.g. "Published".
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-date.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if (! empty($item['status']))
    <div class="t-muted">{{ $item['status'] }}</div>
@endif
<div>{{ is_scalar($value) ? $value : '' }}</div>
