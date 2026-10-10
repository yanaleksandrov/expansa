<?php

use App\Models\User;
use Expansa\Facades\Auth;

/**
 * Output user account button.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/user-account.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$user = User::current();
?>
<details class="details" @click.outside="$el.removeAttribute('open')">
    <summary class="details-summary">
        <span class="expansa-user-name">{{ t('Hi, :Username', $user->showname ?? '') }}</span>
        <span class="avatar avatar--xs" style="background-image: url(https://i.pravatar.cc/150?img=3)">
            <i class="badge bg-green" title="{{ t('Online') }}"></i>
        </span>
    </summary>

    <div class="details-content">
        <?php tree(Auth::getAccounts(), function (array $accounts) { ?>
            <ul class="user-menu">
                <li class="user-menu-divider">{{ t('Switch Account') }}</li>
                @foreach($accounts as $account)
                    <li class="user-menu-item">
                        <a class="user-menu-link" href="#" @click.prevent="$ajax.post('user/switch-account', {login: {{ json_encode($account->identifier) }}})">
                            <i class="ph ph-user-circle"></i> {{ $account->showname ?: $account->identifier }}
                        </a>
                    </li>
                @endforeach
            </ul>
        <?php }); ?>

        <?php tree('dashboard-user-menu', function (array $items) { ?>
            <ul class="user-menu">
                @foreach($items as $item)
                    @if($item->url)
                        <li class="user-menu-item">
                            <a class="user-menu-link" href="{{ $item->url }}"><i class="{{ $item->icon }}"></i> {{ $item->title }}</a>
                            <?php tree($item->children); ?>
                        </li>
                    @else
                        <li class="user-menu-divider">{{ $item->title }}</li>
                    @endif
                @endforeach
            </ul>
        <?php }); ?>
    </div>
</details>
