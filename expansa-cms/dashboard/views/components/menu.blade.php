<?php
/**
 * Expansa dashboard menu template can be overridden by copying it to themes/yourtheme/dashboard/views/components/menu.php
 * Children of a first level item collapse together with deeper levels.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<?php tree('dashboard-main-menu', function (array $items) { ?>
    <ul class="nav" u-data="{i:''}" u-sticky>
        @foreach($items as $item)
            @if(! $item->url)
                <li class="nav__item nav__item--divider">{{ $item->title }}</li>
            @elseif(! $item->children)
                <li class="nav__item">
                    <a class="nav__link" href="{{ $item->url }}"><i class="{{ $item->icon }}"></i> {{ $item->title }}</a>
                </li>
            @else
                <li class="nav__item nav__item--parent">
                    <a class="nav__link" href="{{ $item->url }}" @click.prevent="i = '{{ $item->id }}'">
                        <i class="{{ $item->icon }}"></i> {{ $item->title }}
                        @isset($item->count)
                            <span class="badge badge--blue-lt ml-auto">{{ (int) $item->count }}</span>
                        @endisset
                    </a>

                    <?php tree($item->children, function (array $children) use ($item) { ?>
                        <ul class="nav__list" u-show="i === '{{ $item->id }}'" u-collapse hidden>
                            @foreach($children as $child)
                                @if($child->url)
                                    <li>
                                        <a class="nav__link" href="{{ $child->url }}">{{ $child->title }}</a>
                                        <?php tree($child->children); ?>
                                    </li>
                                @else
                                    <li class="nav__item nav__item--divider">{{ $child->title }}</li>
                                @endif
                            @endforeach
                        </ul>
                    <?php }); ?>
                </li>
            @endif
        @endforeach
    </ul>
<?php }); ?>
