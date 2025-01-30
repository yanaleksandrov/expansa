<?php
/**
 * This file is part of Expansa CMS.
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \App\Tables\Plugins()))->print(); ?>
</div>
