<?php
/**
 * Slug, identifier or key in monospace, with the copy button.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-code.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Code $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<span class="df aic g-1">
    <code>{{ $value->text }}</code>
    @if ($value->copy && $value->text !== '')
        <i class="ph ph-copy t-muted" title="{{ t_attr('Copy') }}" @click="$copy({{ json_encode($value->text) }})"></i>
    @endif
</span>
