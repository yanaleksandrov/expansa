<?php

use Expansa\Facades\Role;

/**
 * Name of the user role in a badge, the green one for administrators.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-role.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$role = is_string($value) ? $value : '';
?>
<span class="badge{{ $role === 'admin' ? ' badge--green-lt' : '' }}">
    <i class="ph ph-person-simple-run"></i> {{ Role::get($role)['name'] ?? '' }}
</span>
