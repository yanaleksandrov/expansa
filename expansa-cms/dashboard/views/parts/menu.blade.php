<?php
/**
 * Expansa dashboard menu template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/menu.php
 * Children of a first level item collapse together with deeper levels.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<?php tree('dashboard-main-menu', function (array $items) { ?>
    <ul class="nav" u-data="{open: '{{ array_find($items, fn ($item) => $item->active)?->id }}'}" u-sticky>
        @foreach($items as $item)
            @if(! $item->url)
                <li class="nav-item nav-item--divider">{{ $item->title }}</li>
            @elseif(! $item->children)
                <li class="nav-item{{ $item->active ? ' is-active' : '' }}">
                    <a class="nav-link" href="{{ $item->url }}"><i class="{{ $item->icon }}"></i> {{ $item->title }}</a>
                </li>
            @else
                <li class="nav-item nav-item--parent{{ $item->active ? ' is-active' : '' }}">
                    <a class="nav-link" href="{{ $item->url }}" @click.prevent="open = open === '{{ $item->id }}' ? '' : '{{ $item->id }}'">
                        <i class="{{ $item->icon }}"></i> {{ $item->title }}
                        @isset($item->count)
                            <span class="badge badge--blue-lt ml-auto">{{ (int) $item->count }}</span>
                        @endisset
                    </a>
                    @isset($item->create)
                        <a class="nav-action" href="{{ url('dashboard/post?type=' . $item->id) }}" title="{{ $item->create }}">
                            <i class="ph ph-plus"></i>
                        </a>
                    @endisset
                    <?php tree($item->children, function (array $children) use ($item) { ?>
                        <ul class="nav-list" u-collapse="open === '{{ $item->id }}'"{{ $item->active ? '' : ' hidden' }}>
                            @foreach($children as $child)
                                @if($child->url)
                                    <li class="nav-item{{ $child->active ? ' is-active' : '' }}">
                                        <a class="nav-link" href="{{ $child->url }}">{{ $child->title }}</a>
                                        <?php tree($child->children); ?>
                                    </li>
                                @else
                                    <li class="nav-item nav-item--divider">{{ $child->title }}</li>
                                @endif
                            @endforeach
                        </ul>
                    <?php }); ?>
                </li>
            @endif
        @endforeach
    </ul>
<?php }); ?>
