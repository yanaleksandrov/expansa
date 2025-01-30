<?php

use Expansa\Facades\Hook;

/**
 * Pages list template can be overridden by copying it to themes/yourtheme/dashboard/views/pages.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

Hook::add('expansa_dashboard_footer', function () {
    echo view('dialogs/posts-editor');
});
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \app\Tables\Pages()))->print(); ?>
</div>
