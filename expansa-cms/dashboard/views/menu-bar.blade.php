<?php

use Expansa\Safe;

/**
 * Expansa dashboard menu top bar template can be overridden by copying it to themes/yourtheme/dashboard/views/menu-bar.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

echo tree('dashboard-menu-bar', function($items) {
    ?>
    <ul id="dashboard-menu-bar" class="menu mr-auto">
        <?php
        foreach ($items as $item) :
            [$url, $icon, $title] = Safe::data($item, [
                'url'   => 'url',
                'icon'  => 'class',
                'title' => 'trim',
            ])->values();
            ?>
            <li class="menu__item">
                <a class="menu__link" href="<?php echo $url; ?>"><?php $icon && printf('<i class="%s"></i> ', $icon); ?><?php echo $title; ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
});
