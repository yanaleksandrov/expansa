<?php
/**
 * Installed plugin: the name with the authors and the actions below.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-plugin.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Extension $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<strong>{{ $value->title }}</strong>
<div class="df g-1 mt-1 t-muted">
    @if ($value->authors)
        <span class="dib">{{ t('by :author', implode(', ', $value->authors)) }}</span>
    @endif
    <div class="table-hidden"> ·
        <a href="#" class="dib">{{ t('Settings') }}</a> · <a href="#" class="dib t-red">{{ t('Delete') }}</a>
    </div>
</div>
