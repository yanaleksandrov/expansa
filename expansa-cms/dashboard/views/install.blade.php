<?php
/*
 * Expansa install wizard.
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="mw-400" x-data="expansa">
    <div class="df jcc">
        <img src="{{ url('/dashboard/assets/images/logo-decorate.svg') }}" width="200" height="117" alt="Expansa CMS">
    </div>
    <?php echo form('system-install', EX_PATH . 'dashboard/forms/system-install.php'); ?>
</div>
