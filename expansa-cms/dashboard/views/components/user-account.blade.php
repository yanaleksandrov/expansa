<?php

/**
 * Output user account button.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/user-account.php
 *
 * @var App\Models\User|null $viewer User who views the page, shared with dashboard views.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<details class="details" @click.outside="$el.removeAttribute('open')">
    <summary class="details-summary" title="{{ t('Online') }}">
        <span class="expansa-user-name">{{ t('Hi, :Username', $viewer->showname ?? '') }}</span>
        <span class="avatar avatar--xs" style="background-image: url({{ url('dashboard/assets/images/avatar.svg') }})">
            <i class="badge badge--green"></i>
        </span>
    </summary>

    <div class="details-content">
        <?php tree('dashboard-user-menu', function (array $items) { ?>
            <ul class="user-menu">
                @foreach($items as $item)
                    @if($item->account !== null)
                        <li class="user-menu-item{{ $item->current ? ' is-active' : '' }}">
                            @if($item->current)
                                <span class="user-menu-link"><i class="ph ph-check"></i> {{ $item->title }}</span>
                            @else
                                <a class="user-menu-link" href="#" @click.prevent="$ajax.post('user/switch-account', {login: {{ json_encode($item->account) }}})">
                                    <i class="{{ $item->icon }}"></i> {{ $item->title }}
                                </a>
                            @endif
                            <a class="user-menu-action" href="{{ $item->sign_out }}" title="{{ t('Sign Out') }}">
                                {{ t('Sign Out') }}
                            </a>
                        </li>
                    @elseif($item->url)
                        <li class="user-menu-item">
                            <a class="user-menu-link" href="{{ $item->url }}"><i class="{{ $item->icon }}"></i> {{ $item->title }}</a>
                            <?php tree($item->children); ?>
                        </li>
                    @else
                        <li class="user-menu-item user-menu-item--divider">{{ $item->title }}</li>
                    @endif
                @endforeach
            </ul>
        <?php }); ?>
    </div>
</details>
