<?php
/**
 * Editable value: a textarea named `{key}[{id}]` by the row id.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-text.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var int|string                  $id    Row id.
 * @var string                      $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<label class="db">
    <textarea name="{{ $cell->key }}[{{ $id }}]" rows="7">{{ $value }}</textarea>
</label>
