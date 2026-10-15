<?php

/**
 * Output user menu button.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/menu-user.php
 *
 * @var App\Models\User|null $viewer User who views the page, shared with dashboard views.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<details class="details" @click.outside="$el.removeAttribute('open')">
    <summary class="details-summary" title="{{ t('Online') }}">
        <strong>{{ t('Hi, :Username', $viewer->showname ?? '') }}</strong>
        <span class="avatar avatar--xs" style="background-image: url({{ url('dashboard/assets/images/avatar.svg') }})">
            <i class="badge badge--green"></i>
        </span>
    </summary>

    <div class="details-content">
        <?php tree('dashboard-user-menu', function (array $items) { ?>
            <ul class="dropdown-menu">
                @foreach($items as $item)
                    @if($item->account !== null)
                        <li class="dropdown-menu__item{{ $item->current ? ' is-active' : '' }}">
                            @if($item->current)
                                <span class="dropdown-menu__link"><i class="ph ph-check"></i> {{ $item->title }}</span>
                            @else
                                <a class="dropdown-menu__link" href="#" @click.prevent="$ajax.post('user/switch-account', {login: {{ json_encode($item->account) }}})">
                                    <i class="{{ $item->icon }}"></i> {{ $item->title }}
                                </a>
                            @endif
                            <a class="dropdown-menu__action" href="{{ $item->sign_out }}" title="{{ t('Sign Out') }}">
                                {{ t('Sign Out') }}
                            </a>
                        </li>
                    @elseif($item->url)
                        <li class="dropdown-menu__item">
                            <a class="dropdown-menu__link" href="{{ $item->url }}"><i class="{{ $item->icon }}"></i> {{ $item->title }}</a>
                            <?php tree($item->children); ?>
                        </li>
                    @else
                        <li class="dropdown-menu__item dropdown-menu__item--divider">{{ $item->title }}</li>
                    @endif
                @endforeach
            </ul>
        <?php }); ?>
    </div>
</details>
