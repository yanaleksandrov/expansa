<?php

use Expansa\Facades\Safe;

/**
 * Table image cell
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-image.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$class = Safe::class($__data['key'] ?? []);
$prop  = Safe::prop($__data['key'] ?? []);
?>
<div class="<?php echo $class; ?>">
    <span class="avatar avatar--rounded" style="background-image: url({{ $__data[$prop] ?? '' }})"></span>
</div>
