<?php

use Expansa\Builders\Form;

/**
 * Terms editor.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/terms.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <div class="terms">
        <div class="terms-side">
            <?php Form::make(EX_DASHBOARD . 'forms/terms-editor.php'); ?>
        </div>
        <div class="terms-main">
            <?php (new \Expansa\Builders\Table(new \app\Tables\Terms()))->print(); ?>
        </div>
    </div>
</div>
