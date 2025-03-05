<?php

declare(strict_types=1);

use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Support\Is;

// launch the installer if Expansa is not installed.
if (! Is::installed()) {
    $suffix = ! Is::debug() ? '.min' : '';
    foreach (['expansa', 'controls', 'utility', 'phosphor'] as $style) {
        Asset::style($style, url("/dashboard/assets/css/$style$suffix.css"));
    }

    foreach (['expansa', 'ajax', 'alpine'] as $script) {
        $data = [];
        if ($script === 'expansa') {
            $data['data'] = [
                'apiurl'         => url('/api/'),
                'spriteFlagsUrl' => url('/dashboard/assets/sprites/flags.svg'),
            ];
        }
        Asset::script($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
    }

    Hook::configure(EX_PATH . 'app/Listeners');

    // register Expansa routes
    require_once EX_PATH . 'routes/api.php';
    require_once EX_PATH . 'routes/web.php';
    exit;
}
