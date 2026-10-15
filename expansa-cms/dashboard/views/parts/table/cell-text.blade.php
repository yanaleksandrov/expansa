<?php
/**
 * Editable value: a textarea named `{key}[{id}]` by the row id.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-text.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<label class="db">
    <textarea name="{{ $cell->key }}[{{ $item['id'] ?? '' }}]" rows="1" u-textarea="7">{{ is_scalar($value) ? $value : '' }}</textarea>
</label>
