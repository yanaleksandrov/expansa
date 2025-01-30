<?php
/**
 * Terms list template can be overridden by copying it to themes/yourtheme/dashboard/views/terms.php
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
            <?php echo form('terms-editor', EX_DASHBOARD . 'forms/terms-editor.php'); ?>
        </div>
        <div class="terms-main">
            <?php (new \Expansa\Builders\Table(new \App\Tables\Terms()))->print(); ?>
        </div>
    </div>
</div>
