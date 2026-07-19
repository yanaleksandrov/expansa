<?php

use App\Models\Options;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Facades\Safe;

/**
 * Remove the duplicate access to the console at two addresses:
 * "dashboard" and "dashboard/index", leave only the first one.
 */
if (!defined('EX_PATH')) {
    $dashboardUrl = trim($_SERVER['SCRIPT_URI'] ?? '');
    if ($dashboardUrl) {
        header('Location: ' . $dashboardUrl . 'profile');
    }
    exit;
}

$slug = Safe::trim($__data['slug'] ?? '');
$table = $__data['table'] ?? null;
?>
        <!DOCTYPE html>
<html lang="<?php echo I18n::locale(); ?>">
<head>
    <meta charset="{{ Options::attr( 'charset', 'UTF-8' ) }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
          rel="stylesheet">
    <style>
        :root {
            --expansa-font-text: "Inter", sustem-ui, sans-serif !important;
        }
    </style>
    <?php
    /**
     * Prints scripts or data before the closing body tag on the dashboard.
     *
     * @since 2025.1
     */
    Hook::call('renderDashboardHeader');
    ?>
</head>
<body x-data="expansa" @keydown.window.prevent.ctrl.s="$notification.add(notifications.ctrlS)">
<div class="expansa" :class="showMenu && 'active'">
    <div class="expansa-bar">
        <div class="expansa-bar-burger" :class="showMenu && 'active'" @click="showMenu = !showMenu">
            <i class="ph ph-list"></i>
        </div>

        <div class="expansa-bar-menu">
            <?php echo view('menu-bar'); ?>
        </div>

        <div class="expansa-bar-search">
            <?php echo view('parts/search'); ?>
        </div>

        <div class="expansa-bar-account">
            <?php echo view('global/user-account'); ?>
        </div>
    </div>

    <div class="expansa-panel">
        <a href="<?php echo url(); ?>" target="_blank">
            <img src="<?php echo url( '/dashboard/assets/images/logo.svg' ); ?>" width="34" height="34"
                 alt="Expansa Logo">
        </a>
        <?php echo view('menu-panel'); ?>
    </div>

    <div class="expansa-side">
        <?php echo view('menu'); ?>
    </div>

    <div class="expansa-main">
        <?php echo view($slug, $__data ?? []); ?>
    </div>

    <div class="expansa-board">
        <a href="#" class="dif g-1 aic t-dark" title="Get Support"><i class="ph ph-headset fs-12"></i> support</a>
        <a href="#" class="dif g-1 aic t-dark" title="Expansa CMS version"><i class="ph ph-git-branch fs-12"></i> 2025.1</a>
    </div>
</div>

<!-- dialog windows start -->
<div class="dialog" :class="$store.dialog?.class" id="expansa-dialog">
    <div class="dialog-wrapper" @click.outside="$dialog.close()">
        <div class="dialog-header">
            <template x-if="$store.dialog?.title">
                <h6 class="dialog-title" x-text="$store.dialog.title"></h6>
            </template>
            <button class="dialog-close" type="button" @click="$dialog.close()"></button>
        </div>
        <div class="dialog-content" data-content></div>
    </div>
</div>

<!-- notifications start -->
<template x-if="$store.notifications.length">
    <div class="notifications">
        <template x-for="(notification, i) in $store.notifications">
            <div class="notifications-item" :class="notification.class"
                 :style="`--notice-scale: ${1 - ($store.notifications.length - i - 1) * 0.005}`">
                <div class="notifications-wrapper">
                    <template x-if="notification.type">
                        <div class="notifications-icon">
                            <i class="ph ph-bell-ringing t-gray" x-show="notification.type === 'info'"></i>
                            <i class="ph ph-siren t-red" x-show="notification.type === 'error'"></i>
                            <i class="ph ph-check t-green" x-show="notification.type === 'success'"></i>
                            <i class="ph ph-shield-warning t-orange" x-show="notification.type === 'warning'"></i>
                        </div>
                    </template>
                    <div class="notifications-text" x-text="notification.message"></div>
                    <div class="notifications-close"
                         :style="notification.duration && `--notice-animation: ${notification.animation}`"
                         @click="$notification.close(notification.id)"></div>
                </div>
            </div>
        </template>
    </div>
</template>
<?php
/**
 * Prints scripts or data before the closing body tag on the dashboard.
 *
 * @since 2025.1
 */
Hook::call('renderDashboardFooter');
?>
</body>
</html>