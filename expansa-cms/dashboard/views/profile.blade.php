<?php
/**
 * User profile template can be overridden by copying it to themes/yourtheme/dashboard/views/profile.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
{!! form('user-profile', EX_DASHBOARD . 'forms/user-profile.php') !!}
