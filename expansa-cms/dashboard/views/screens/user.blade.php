<?php
/**
 * Account of a user for an administrator: status, devices, security log and actions; the data comes from
 * App\Dashboard\Pages\User. This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/user.php
 *
 * @var App\Models\User $user
 * @var string[]        $roles
 * @var bool            $isActive
 * @var bool            $isSelf
 * @var bool            $hasTwoFactor
 * @var bool            $canImpersonate
 * @var array[]         $sessions
 * @var array[]         $events
 *
 * @package Expansa\Templates
 */
?>
<div class="dg g-7 p-7 sm:p-5 mw-900" u-data>
	<div class="dg g-1">
		<h2>{{ $user->showname ?: $user->login }}</h2>
		<div class="t-muted">
			{{ $user->login }} · {{ $user->email }} · {{ implode(', ', $roles) ?: t('No role') }} ·
			{{ $isActive ? t('Active') : t('Disabled') }}
			@if($hasTwoFactor)
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
			@if(!$isSelf)
				<button class="btn btn--outline {{ $isActive ? 't-red' : '' }}" type="button" @click="$ajax.post('user/admin-status', {id: {{ $user->id }}, active: {{ $isActive ? 0 : 1 }}, password: adminPassword})">
					<i class="ph ph-power"></i> {{ $isActive ? t('Disable') : t('Turn On') }}
				</button>
			@endif
			<button class="btn btn--outline" type="button" @click="$ajax.post('user/admin-sign-out', {id: {{ $user->id }}, password: adminPassword})">
				<i class="ph ph-sign-out"></i> {{ t('Sign Out Everywhere') }}
			</button>
			<button class="btn btn--outline" type="button" @click="$ajax.post('user/admin-password-reset', {id: {{ $user->id }}})">
				<i class="ph ph-envelope-simple"></i> {{ t('Send Password Reset') }}
			</button>
			@if($hasTwoFactor)
				<button class="btn btn--outline" type="button" @click="$ajax.post('user/admin-two-factor-disable', {id: {{ $user->id }}, password: adminPassword})">
					<i class="ph ph-device-mobile-slash"></i> {{ t('Turn Off Two-Factor Authentication') }}
				</button>
			@endif
			@if($canImpersonate)
				<button class="btn btn--outline" type="button" @click="$ajax.post('user/impersonate', {id: {{ $user->id }}, password: adminPassword})">
					<i class="ph ph-user-switch"></i> {{ t('Sign In as This User') }}
				</button>
			@endif
		</div>
	</div>

	<div class="dg g-2">
		<h5>{{ t('Devices') }}</h5>
		@foreach($sessions as $session)
			<div class="fs-13">{{ $session['device'] }} · {{ $session['ip'] }} · {{ t('Last active :date', $session['active']) }}</div>
		@endforeach
	</div>

	<div class="dg g-2">
		<h5>{{ t('Recent Activity') }}</h5>
		@foreach($events as $event)
			<div class="fs-13">
				{{ $event['label'] }}
				@if(isset($event['details']['by']))
					({{ t('by :login', $event['details']['by']) }})
				@endif
				<span class="t-muted">{{ $event['device'] }} · {{ $event['ip'] }} · {{ $event['date'] }}</span>
			</div>
		@endforeach
	</div>
</div>
