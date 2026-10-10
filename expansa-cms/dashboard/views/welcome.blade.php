<?php
/**
 * Document of a dashboard page: the bar, the menus and the dialogs around the page template.
 *
 * @var string           $page  Template of the page, e.g. `screens/user`; it gets the same data.
 * @var string           $title Document title.
 * @var App\Support\Site $site  Settings of the site: name, language, charset, addresses, version; shared with every view.
 */

use Expansa\Facades\Hook;
?>
<!DOCTYPE html>
<html lang="{{ $site->locale }}">
<head>
    <meta charset="{{ $site->charset }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Expansa' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
    :root {
        --expansa-font-text: "Inter", sustem-ui, sans-serif !important;
    }
    </style>
    <?php
    /**
     * Prints scripts or data before the closing body tag on the dashboard.
     *
     * @since 2027.1
     */
    Hook::run('renderDashboardHeader');
    ?>
</head>
<body u-data="youla" @keydown.window.prevent.ctrl.s="$notice.add(notifications.ctrlS)">
    <?php echo view('components/impersonation'); ?>
    <div class="expansa" :class="showMenu && 'active'">
        <div class="expansa-bar">
            <div class="expansa-bar-burger" :class="showMenu && 'active'" @click="showMenu = !showMenu">
                <i class="ph ph-list"></i>
            </div>

            <div class="expansa-bar-menu">
                <?php echo view('components/menu-bar'); ?>
            </div>

            <div class="expansa-bar-search">
                <?php echo view('components/search'); ?>
            </div>

            <div class="expansa-bar-account">
                <?php echo view('components/user-account'); ?>
            </div>
        </div>

        <div class="expansa-panel">
            <a href="{{ $site->url }}" target="_blank">
                <img src="<?php echo url( '/dashboard/assets/images/logo.svg' ); ?>" width="34" height="34" alt="Expansa Logo">
            </a>
            <?php echo view('components/menu-panel'); ?>
        </div>

        <div class="expansa-side">
            <?php echo view('components/menu'); ?>
        </div>

        <div class="expansa-main">
            <?php echo view($page, $__data); ?>
        </div>

        <div class="expansa-board">
            <a href="#" class="dif g-1 aic t-dark" title="Get Support"><i class="ph ph-headset fs-12"></i> support</a>
            <a href="#" class="dif g-1 aic t-dark" title="Expansa CMS version"><i class="ph ph-git-branch fs-12"></i> 2027.1</a>
        </div>
    </div>

    <!-- dialog windows start -->
    <div class="dialog" u-data="dialog" :class="stack.length ? ['active', stack.at(-1)?.class].filter(Boolean).join(' ') : ''" @keydown.esc.window="close()" id="expansa-dialog">
        <div u-each="entry in stack" class="dialog-wrapper" @click.outside="close(entry.id)">
            <div class="dialog-header">
                <template u-if="entry.title">
                    <h6 class="dialog-title" u-text="entry.title"></h6>
                </template>
                <button class="dialog-close" type="button" @click="close(entry.id)"></button>
            </div>
            <div class="dialog-content" u-html="entry.content"></div>
        </div>
    </div>

    <?php
    /**
     * Media library dialog start: registered globally (not per-field/page) since it must
     * be reachable from anywhere - see src/js/youla-storage.js and dialogs/media-library.blade.php
     *
     * @since 2027.1
     */
    echo view('components/dialogs/media-library');

    /**
     * Prints scripts or data before the closing body tag on the dashboard.
     *
     * @since 2027.1
     */
    Hook::run('renderDashboardFooter');
    ?>
</body>
</html>
