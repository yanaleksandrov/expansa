<?php
/**
 * Switch on for a true value, its value is the row id: the `@change` attribute of the column
 * gets the switch as `$event.target`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-toggle.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="field">
    <div class="field-item">
        <input type="checkbox" name="{{ $cell->key }}[]" value="{{ $item['id'] ?? '' }}"{{ $value === true ? ' checked' : '' }}>
    </div>
</div>
