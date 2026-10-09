<?php
/**
 * User sign-in template, the data comes from App\Dashboard\Pages\SignIn.
 * It can be overridden by copying it to themes/yourtheme/dashboard/views/screens/sign-in.php
 *
 * @var string                $error      Message of a failed sign-in by a provider.
 * @var string                $notice     Message of an account link: email confirmed, devices signed out.
 * @var array[]               $providers  OAuth sign-in buttons: `id`, `label`, `url`.
 * @var bool                  $add        Signing in to one more account.
 * @var bool                  $challenged The password is right, the second factor is asked.
 * @var bool                  $emailLink  Signing in by an emailed link is on.
 * @var bool                  $membership Anyone can sign up.
 *
 * @package Expansa\Templates
 */
?>
<main class="mw-360" u-data>
	<a href="{{ url() }}" class="df jcc mb-4" target="_blank">
		<img src="{{ url('dashboard/assets/images/logo-grid.svg') }}" width="212" height="124" alt="Expansa CMS">
	</a>
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
				<h4>{!! t('Two-Factor Authentication') !!}</h4>
				<div class="t-muted">{!! t('Enter the 6-digit code of your authenticator app, or a recovery code.') !!}</div>
			</div>
			<div class="field field--lg">
				<div class="field-item">
					<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="11" required autofocus placeholder="{!! t_attr('Code') !!}">
				</div>
			</div>
			<button type="submit" class="btn btn--lg btn--primary btn--full">{!! t('Continue') !!}</button>
			<a href="{{ url('sign-in') }}" class="fs-13 t-center t-muted">{!! t('Sign In with Another Account') !!}</a>
		</form>
	@else
	<?php
	echo form('user-sign-in', EX_DASHBOARD . 'forms/user-sign-in.php');
    ?>
	@if($providers)
		<div class="dg g-2 mt-2">
			@foreach($providers as $provider)
				<a href="{{ $provider['url'] }}" class="btn btn--lg btn--outline btn--full">
					<i class="ph ph-{{ $provider['id'] }}-logo"></i> {{ t('Continue with :provider', $provider['label']) }}
				</a>
			@endforeach
		</div>
	@endif
	@if($emailLink)
		<details class="mt-3 fs-13">
			<summary class="t-center t-muted">{!! t('Email Me a Sign-In Link') !!}</summary>
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
