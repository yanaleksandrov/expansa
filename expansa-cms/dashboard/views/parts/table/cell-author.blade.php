<?php
/**
 * Author of a comment: the name, linking to the profile of a registered author, with the email below.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-author.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if (! empty($item['author_id']))
    <a href="{{ url('dashboard/profile?id=' . $item['author_id']) }}" class="fw-500 t-dark">{{ is_scalar($value) ? $value : '' }}</a>
@else
    <strong>{{ is_scalar($value) ? $value : '' }}</strong>
@endif
<div class="fs-12 t-muted mt-1">{{ $item['email'] ?? '' }}</div>
