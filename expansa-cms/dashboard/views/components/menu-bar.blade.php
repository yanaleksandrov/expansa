<?php
/**
 * Expansa dashboard menu top bar template can be overridden by copying it to themes/yourtheme/dashboard/views/components/menu-bar.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<?php tree('dashboard-menu-bar', function (array $items) { ?>
    <ul id="dashboard-menu-bar" class="menu">
        @foreach($items as $item)
            <li class="menu__item">
                <a class="menu__link" href="{{ $item->url }}">
                    @if($item->icon)
                        <i class="{{ $item->icon }}"></i>
                    @endif

                    {{ $item->title }}
                </a>
            </li>
        @endforeach
    </ul>
<?php }); ?>
