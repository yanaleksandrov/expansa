<?php
/*
 * Expansa installed page.
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="mw-400 df fdc jcc">
    <?php
    echo view('global/state', [
        'icon'        => 'success',
        'title'       => t('Woo-hoo, Expansa has been successfully installed!'),
        'description' => t('We hope the installation process was easy. Thank you, and enjoy.'),
    ])->render();
    ?>
    <a href="{{ url('/dashboard/profile') }}" class="btn btn--lg btn--primary">{{ t('Go to dashboard') }}</a>
</div>
