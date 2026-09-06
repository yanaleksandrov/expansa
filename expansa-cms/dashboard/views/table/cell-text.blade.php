<?php

use Expansa\Facades\Safe;

/**
 * Table title with actions cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-text.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$class = Safe::class($__data['key'] ?? [] );
$prop  = Safe::prop($__data['key'] ?? [] );
$value = Safe::trim($__data['value'] ?? '' );
?>
<label class="<?php echo $class; ?>">
    <textarea :name="`items[${i}]`" u-text="item.<?php echo $prop; ?>" rows="1" u-textarea="7"></textarea>
</label>
