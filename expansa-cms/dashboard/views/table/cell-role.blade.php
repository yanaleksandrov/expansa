<?php

use App\User\Roles;
use Expansa\Facades\Safe;

/**
 * Badge
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-badge.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$prop = Safe::prop($__data['key'] ?? '');
$role = Roles::get($__data[$prop] ?? '');
$name = $role['name'] ?? '';
?>
<span class="badge {{ $__data[$prop] === 'admin' ? 'badge--green-lt' : '' }}">
	<i class="ph ph-person-simple-run"></i> {{ $name }}
</span>
