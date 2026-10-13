<?php
/**
 * User name linking to the profile of the user, with the login and email below.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-user.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="fs-14 lh-sm">
    <a href="{{ url('dashboard/profile?id=' . ($item['id'] ?? 0)) }}" class="fw-500 t-dark">{{ is_scalar($value) ? $value : '' }}</a>
</div>
<div class="fs-12 t-muted mt-1">{{ $item['login'] ?? '' }} · {{ $item['email'] ?? '' }}</div>
