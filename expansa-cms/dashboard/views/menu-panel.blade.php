<?php
/**
 * Expansa dashboard menu panel template can be overridden by copying it to themes/yourtheme/dashboard/views/menu-panel.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

echo tree('dashboard-panel-menu', $test = function ($items, $tree) use (&$test) {
    if (empty($items) || !is_array($items)) {
        return false;
    }
    ?>
    <ul class="panel">
        <?php
        foreach ($items as $item) {
            ob_start();
            ?>
            <li class="panel__item" x-tooltip.hover.right="'%title$s'">
                <a class="panel__link" href="%url$s"><i class="%icon$s"></i></a>
            </li>
            <?php
            echo $tree->vsprintf(ob_get_clean(), $item);
        }
        ?>
    </ul>
    <?php
});
