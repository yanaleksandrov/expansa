<?php
/**
 * Date in `<time>` with the full date in the tooltip and a muted note above it, e.g. the status of the post.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-date.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Date $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if ($value->note)
    <div class="fs-12 t-muted">{{ $value->note }}</div>
@endif
@if ($value->datetime)
    <time datetime="{{ $value->datetime }}"@if ($value->title) title="{{ $value->title }}"@endif>{{ $value->text }}</time>
@else
    <span class="t-muted">{{ $value->text }}</span>
@endif
