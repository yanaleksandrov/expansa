<?php

use Expansa\Support\Arr;

/**
 * Actions of a row in the dropdown menu of the "⋯" button, for narrow tables instead of the quick links of a title.
 * The popover is anchored to the button, the browser closes it on a click outside and on Esc.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-menu.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Link[] $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$popover = 'table-menu-' . bin2hex(random_bytes(4));
?>
@if ($value)
    <div class="details">
        <button class="btn btn--xs btn--icon" type="button" popovertarget="{{ $popover }}" title="{{ t_attr('Actions') }}"><i class="ph ph-dots-three"></i></button>
        <div class="details-content" id="{{ $popover }}" popover>
            <ul class="dropdown-menu">
                @foreach ($value as $link)
                    <li class="dropdown-menu__item">
                        <a class="{{ trim('dropdown-menu__link ' . $link->class) }}" href="{{ $link->url ?: '#' }}"{!! Arr::toHtmlAttributes($link->attributes) !!}>
                            @if ($link->icon)
                                <i class="{{ $link->icon }}"></i>
                            @endif
                            {{ $link->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
