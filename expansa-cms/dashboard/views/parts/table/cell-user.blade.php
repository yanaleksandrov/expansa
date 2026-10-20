<?php
/**
 * User name linking to the profile of the user, with the login and email below.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-user.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Person $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<a href="{{ $value->url }}" class="fw-500 fs-14 lh-sm t-dark">{{ $value->name }}</a>
<div class="fs-12 t-muted">{{ $value->login }} · {{ $value->email }}</div>
