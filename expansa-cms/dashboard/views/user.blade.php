<?php
/**
 * Account of a user for an administrator: status, devices, security log and actions.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/user.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

use App\Api\User\Admin;
use App\Api\User\Events;
use App\Api\User\Sessions;
use App\Api\User\TwoFactor;
use App\Models\User;
use Expansa\Facades\Access;
use Expansa\Facades\I18n;
use Expansa\Facades\Role;

$admin = User::current();
$user  = Access::allows($admin, 'users_edit') ? Admin::find((int) ($_GET['id'] ?? 0)) : null;
$date  = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
?>
@if(!Access::allows($admin, 'users_edit'))
	<div class="df fdc aic jcc t-center t-muted">
		<h1 class="fs-64">403</h1>
		<p>{{ t('You are not allowed to open this page.') }}</p>
	</div>
@elseif(!$user)
	<div class="df fdc aic jcc t-center t-muted">
		<h1 class="fs-64">404</h1>
		<p>{{ t('User not found.') }}</p>
	</div>
@else
	<?php
	$isActive  = $user->status === User::STATUS_ACTIVE;
	$id        = $user->id;
	$roles     = array_map(fn (string $role) => Role::get($role)['name'] ?? $role, $user->roles);
	$canImpersonate = $user->id !== $admin->id && $isActive && ! Access::allows($user, 'users_edit');
	?>
	<div class="dg g-7 p-7 sm:p-5 mw-900" u-data>
		<div class="dg g-1">
			<h2>{{ $user->showname ?: $user->login }}</h2>
			<div class="t-muted">
				{{ $user->login }} · {{ $user->email }} · {{ implode(', ', $roles) ?: t('No role') }} ·
				{{ $isActive ? t('Active') : t('Disabled') }}
				@if(TwoFactor::isEnabled($user))
					· {{ t('Two-factor authentication on') }}
				@endif
			</div>
		</div>

		<div class="dg g-2">
			<h5>{{ t('Actions') }}</h5>
			<div class="t-muted fs-13">{{ t('Disabling, signing out, turning off two-factor authentication and signing in as the user need your current password.') }}</div>
			<div class="df aic fw g-2">
				<div class="field">
					<div class="field-item">
						<input type="password" u-prop="adminPassword" autocomplete="current-password" placeholder="{{ t_attr('Your current password') }}">
					</div>
				</div>
				@if($user->id !== $admin->id)
					<button class="btn btn--outline {{ $isActive ? 't-red' : '' }}" type="button" @click="$ajax.post('user/admin-status', {id: {{ $id }}, active: {{ $isActive ? 0 : 1 }}, password: adminPassword})">
						<i class="ph ph-power"></i> {{ $isActive ? t('Disable') : t('Turn on') }}
					</button>
				@endif
				<button class="btn btn--outline" type="button" @click="$ajax.post('user/admin-sign-out', {id: {{ $id }}, password: adminPassword})">
					<i class="ph ph-sign-out"></i> {{ t('Sign out everywhere') }}
				</button>
				<button class="btn btn--outline" type="button" @click="$ajax.post('user/admin-password-reset', {id: {{ $id }}})">
					<i class="ph ph-envelope-simple"></i> {{ t('Send password reset') }}
				</button>
				@if(TwoFactor::isEnabled($user))
					<button class="btn btn--outline" type="button" @click="$ajax.post('user/admin-two-factor-disable', {id: {{ $id }}, password: adminPassword})">
						<i class="ph ph-device-mobile-slash"></i> {{ t('Turn off two-factor authentication') }}
					</button>
				@endif
				@if($canImpersonate)
					<button class="btn btn--outline" type="button" @click="$ajax.post('user/impersonate', {id: {{ $id }}, password: adminPassword})">
						<i class="ph ph-user-switch"></i> {{ t('Sign in as this user') }}
					</button>
				@endif
			</div>
		</div>

		<div class="dg g-2">
			<h5>{{ t('Devices') }}</h5>
			@foreach(Sessions::all($user) as $session)
				<div class="fs-13">{{ $session['device'] }} · {{ $session['ip'] }} · {{ t('Last active :date', $date->format(strtotime($session['used_at'] ?: $session['created_at']))) }}</div>
			@endforeach
		</div>

		<div class="dg g-2">
			<h5>{{ t('Recent activity') }}</h5>
			@foreach(Events::all($user, 50) as $event)
				<div class="fs-13">
					{{ Events::label($event['event']) }}
					@if(isset($event['details']['by']))
						({{ t('by :login', $event['details']['by']) }})
					@endif
					<span class="t-muted">{{ $event['device'] }} · {{ $event['ip'] }} · {{ $date->format(strtotime($event['created_at'])) }}</span>
				</div>
			@endforeach
		</div>
	</div>
@endif
