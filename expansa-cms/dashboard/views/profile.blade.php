<?php
/**
 * User profile template can be overridden by copying it to themes/yourtheme/dashboard/views/profile.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    {!! form('user-profile', EX_DASHBOARD . 'forms/user-profile.php') !!}
</div>
