<?php
/**
 * Expansa dashboard menu panel template can be overridden by copying it to themes/yourtheme/dashboard/views/components/menu-panel.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<?php tree('dashboard-panel-menu', function (array $items) { ?>
    <ul class="panel">
        @foreach($items as $item)
            <li class="panel__item" u-tooltip.hover.right="'{{ $item->title }}'">
                <a class="panel__link" href="{{ $item->url }}"><i class="{{ $item->icon }}"></i></a>
            </li>
        @endforeach
    </ul>
<?php }); ?>
