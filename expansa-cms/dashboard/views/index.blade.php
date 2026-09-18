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
        header('Location: ' . $dashboardUrl . 'chat');
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
<body u-data="youla" @keydown.window.prevent.ctrl.s="$notice.add(notifications.ctrlS)">
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
                <template u-if="$store.dialog?.title">
                    <h6 class="dialog-title" u-text="$store.dialog.title"></h6>
                </template>
                <button class="dialog-close" type="button" @click="$dialog.close()"></button>
            </div>
            <div class="dialog-content" data-content></div>
        </div>
    </div>

    <!-- notifications start -->
    <div class="notifications" u-data="notice" @mouseenter="pause()" @mouseleave="resume()">
        <div u-each="(item, index) in items" class="notifications-item" :class="item.classes()" :style="`--notice-scale: ${1 - (items.length - index - 1) * 0.005}`">
            <div class="notifications-wrapper">
                <i class="ph" :class="`ph-${item.type === 'info' ? 'bell-ringing' : item.type === 'error' ? 'siren' : item.type === 'success' ? 'check' : 'shield-warning'} t-${item.type === 'info' ? 'gray' : item.type === 'error' ? 'red' : item.type === 'success' ? 'green' : 'orange'}`"></i>
                <div class="notifications-text" u-text="item.message"></div>
                <button type="button" class="notifications-close" u-show="item.closable" @click="close(item.id)">
                    <svg class="notifications-spinner" viewBox="0 0 24 24" width="24" height="24">
                        <circle cx="12" cy="12" r="11" @load="$el.style.animationDuration = item.duration + 'ms'; $el.style.animationDelay = '-' + elapsed(item) + 'ms'"></circle>
                    </svg>
                </button>
            </div>
        </div>
    </div>

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
