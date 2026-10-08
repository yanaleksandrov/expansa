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
	@if($membership)
		<div class="fs-13 t-center t-muted mt-3">
			{!! t("Don't have an account yet? [Sign Up](:signUpLink)", url('sign-up')) !!}
		</div>
	@endif
</main>
