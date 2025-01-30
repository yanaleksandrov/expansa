<?php

declare(strict_types=1);

use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Support\Is;

// launch the installer if Expansa is not installed.
if (! Is::installed()) {
    if (!defined('EX_IS_INSTALL')) {
        define('EX_IS_INSTALL', true);
    }

    $suffix = ! Is::debug() ? '.min' : '';
    foreach (['expansa', 'controls', 'utility', 'phosphor'] as $style) {
        Asset::enqueue($style, url("/dashboard/assets/css/$style$suffix.css"));
    }

    foreach (['expansa', 'ajax', 'alpine'] as $script) {
        $data = [];
        if ($script === 'expansa') {
            $data['data'] = [
                'apiurl'         => url('/api/'),
                'spriteFlagsUrl' => url('/dashboard/assets/sprites/flags.svg'),
            ];
        }
        Asset::enqueue($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
    }

    // adding API endpoints to install the system.
    //Api::configure('/api', sprintf('%sapp/Api', EX_PATH));

    Hook::configure(EX_PATH . 'app/Listeners');
    //
    //$content = view('welcome', ['slug' => 'install']);
    //
    //echo (new Expansa\Support\Html())->beautify($content->render());


    // register Expansa routes
    require_once EX_PATH . 'routes.php';
    exit;
}
