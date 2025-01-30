<?php

use Expansa\Facades\Hook;

/**
 * Files storage template can be overridden by copying it to themes/yourtheme/dashboard/views/media.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

Hook::add('expansa_dashboard_footer', function () {
    echo view('dialogs/media-editor');
    echo view('dialogs/media-uploader');
});
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \App\Tables\Media()))->print(); ?>
    <div x-intersect="$ajax('media/get').then(({posts}) => items = posts)"></div>
</div>
