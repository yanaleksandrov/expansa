<?php
/**
 * Image by its URL as a rounded thumbnail.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-image.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<span class="avatar avatar--rounded avatar--lg" style="background-image: url({{ is_string($value) ? $value : '' }})"></span>
