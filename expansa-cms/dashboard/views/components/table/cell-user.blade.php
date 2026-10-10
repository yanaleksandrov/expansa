<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * User name with the login and email, linking to the user page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-user.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$prop, $attributes] = Safe::data(
    $__data ?? [],
	[
		'key'        => 'prop',
		'attributes' => 'array',
	]
)->values();
?>
<div<?php echo Arr::toHtmlAttributes($attributes); ?>>
    <div class="fs-14 lh-sm">
        <a href="{{ url('dashboard/user?id=' . ($__data['id'] ?? 0)) }}" class="fw-500 t-dark">{{ $__data[$prop] ?? '' }}</a>
    </div>
    <div class="fs-12 t-muted mt-1">{{ $__data['login'] ?? '' }} · {{ $__data['email'] ?? '' }}</div>
</div>
