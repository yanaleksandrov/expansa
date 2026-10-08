<?php
/**
 * User sing-in template can be overridden by copying it to themes/yourtheme/dashboard/views/sign-in.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<main class="mw-360" u-data>
	<a href="{{ url() }}" class="df jcc mb-4" target="_blank">
		<img src="{{ url('dashboard/assets/images/logo-grid.svg') }}" width="212" height="124" alt="Expansa CMS">
	</a>
	<?php
	$errors = [
		'oauth-denied'  => t('Sign-in was cancelled.'),
		'oauth-failed'  => t('Could not sign in with this provider. Please try again.'),
		'oauth-email'   => t('An account with this email already exists. Sign in with it and connect the provider in your profile.'),
		'oauth-closed'  => t('Registration is closed or the provider did not confirm your email.'),
		'oauth-limited' => t('Too many attempts. Try again in a few minutes.'),
		'oauth-refused' => t('This account is disabled or its email is not confirmed yet.'),
	];
	$notices = [
		'email-verified'  => t('Your email is confirmed. You can sign in now.'),
		'account-secured' => t('All devices have been signed out. Check your email to set a new password.'),
		'link-invalid'    => t('This link is invalid or expired.'),
	];
	$error      = $errors[$_GET['error'] ?? ''] ?? '';
	$notice     = $notices[$_GET['notice'] ?? ''] ?? '';
	$redirectTo = (string) ($_GET['redirect_to'] ?? '');
	$membership = (bool) App\Models\Option::get('users.membership');
	$providers = Expansa\Facades\Auth::getProviders();
	$add       = isset($_GET['add']) && Expansa\Facades\Auth::isLoggedIn();
	$challenged = ($_GET['step'] ?? '') === 'two-factor' ? App\Api\User\TwoFactor::getChallengedUser() : null;
	?>
	@if($add)
		<div class="df aic g-1 fs-13 mb-3">
			<i class="ph ph-user-plus"></i> {!! t('Sign in to another account. You can switch back in the user menu. [Cancel](:url)', url('dashboard')) !!}
		</div>
	@endif
	@if($notice)
		<div class="df aic g-1 fs-13 mb-3"><i class="ph ph-info"></i> {{ $notice }}</div>
	@endif
	@if($error)
		<div class="df aic g-1 t-red fs-13 mb-3"><i class="ph ph-warning-circle"></i> {{ $error }}</div>
	@endif
	@if($challenged)
		<form class="dg g-6" @submit.prevent='$ajax.post("user/two-factor")'>
			<div class="dg g-2">
				<h4>{!! t('Two-factor authentication') !!}</h4>
				<div class="t-muted">{!! t('Enter the 6-digit code of your authenticator app, or a recovery code.') !!}</div>
			</div>
			<div class="field field--lg">
				<div class="field-item">
					<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="11" required autofocus placeholder="{!! t_attr('Code') !!}">
				</div>
			</div>
			<button type="submit" class="btn btn--lg btn--primary btn--full">{!! t('Continue') !!}</button>
			<a href="{{ url('sign-in') }}" class="fs-13 t-center t-muted">{!! t('Sign in with another account') !!}</a>
		</form>
	@else
	<?php
	echo form('user-sign-in', EX_DASHBOARD . 'forms/user-sign-in.php');
    ?>
	@if($providers)
		<div class="dg g-2 mt-2">
			@foreach($providers as $provider)
				<a href="{{ url("oauth/$provider?" . http_build_query(array_filter(['add' => $add ? 1 : null, 'redirect_to' => $redirectTo]))) }}" class="btn btn--lg btn--outline btn--full">
					<i class="ph ph-{{ $provider }}-logo"></i> {{ t('Continue with :provider', App\Api\User\Identities::label($provider)) }}
				</a>
			@endforeach
		</div>
	@endif
	@if(App\Api\User\EmailLink::isEnabled())
		<details class="mt-3 fs-13">
			<summary class="t-center t-muted">{!! t('Email me a sign-in link') !!}</summary>
			<div class="df aic g-2 mt-2">
				<div class="field">
					<div class="field-item">
						<input type="email" id="email-link" autocomplete="email" placeholder="{!! t_attr('Your email') !!}">
					</div>
				</div>
				<button class="btn btn--outline" type="button" @click="$ajax.post('user/email-link', {email: document.getElementById('email-link').value, redirect_to: new URLSearchParams(location.search).get('redirect_to') || ''})">{!! t('Send') !!}</button>
			</div>
		</details>
	@endif
	@if($membership)
		<div class="fs-13 t-center t-muted mt-3">
			{!! t("Don't have an account yet? [Sign Up](:signUpLink)", url('sign-up')) !!}
		</div>
	@endif
	@endif
</main>
