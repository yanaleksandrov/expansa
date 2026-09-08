<?php

declare(strict_types=1);

use App\Controllers\Web;
use App\Http\VerifyCsrfToken;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Facades\Route;
use Expansa\Support\Is;

// launch the installer if Expansa is not installed.
if (! Is::installed()) {
    VerifyCsrfToken::seed();

    $suffix = ! Is::debug() ? '.min' : '';
    foreach (['expansa', 'controls', 'utility', 'phosphor'] as $style) {
        Asset::style($style, url("/dashboard/assets/css/$style$suffix.css"));
    }

    foreach (['youla-expansa', 'youla-ajax', 'youla-select', 'youla'] as $script) {
        $data = [];
        if ($script === 'youla') {
            $data['data'] = [
                'apiurl'   => url('/api/'),
                'flagsUrl' => url('/dashboard/assets/sprites/flags.svg'),
            ];
        }
        Asset::script($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
    }

    Hook::configure(EX_PATH . 'app/Listeners');

    // register Expansa routes
    require_once EX_PATH . 'routes/api.php';
    Route::get('/(.*)', [Web::class, 'index']);
    Route::run();
    exit;
}
