<?php
/**
 * Device: the icon and the name of the browser and the system, the IP address below
 * and the badge on the device of the viewer.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-device.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Device $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="df aic g-3">
    <i class="{{ $value->icon }} fs-24 t-muted"></i>
    <div>
        <span class="fw-500">{{ $value->name }}</span>
        @if ($value->current)
            <span class="badge badge--green-lt">{{ t('This Device') }}</span>
        @endif
        @if ($value->ip)
            <div class="fs-12 t-muted mt-1">{{ $value->ip }}</div>
        @endif
    </div>
</div>
