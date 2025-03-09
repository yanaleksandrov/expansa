<?php
/**
 * User profile template can be overridden by copying it to themes/yourtheme/dashboard/views/multilingual-settings.blade.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
{!! form('multilingual-settings', EX_DASHBOARD . 'forms/multilingual-settings.php') !!}
