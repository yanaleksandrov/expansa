<?php
/**
 * Two-factor authentication of the profile, also returned by the "user/two-factor-*" API.
 *
 * @var App\Models\User                     $user
 * @var array{secret: string, qr: string}|null $setup Secret being set up.
 * @var string[]|null                        $codes Recovery codes to show once.
 */
defined('EX_PATH') || exit;

$setup     = $setup ?? null;
$codes     = $codes ?? null;
$isEnabled = App\Api\User\TwoFactor::isEnabled( $user );
?>
@if($codes)
	<div class="dg g-2">
		<div class="df aic g-1"><i class="ph ph-check-circle"></i> {!! t( 'Two-factor authentication is on.' ) !!}</div>
		<div>{!! t( 'Save these recovery codes somewhere safe. Each works once when your phone is not at hand; they are not shown again.' ) !!}</div>
		<code class="dg g-1 p-4 card card-border fs-14">
			@foreach($codes as $code)
				<span>{{ $code }}</span>
			@endforeach
		</code>
	</div>
@elseif($setup)
	<div class="dg g-3">
		<div>{!! t( 'Scan the code with an authenticator app (Google Authenticator, 1Password, Authy, etc.) or type the key, then enter the 6-digit code it shows.' ) !!}</div>
		<div class="df fw aic g-4">
			{!! $setup['qr'] !!}
			<code class="fs-14">{{ $setup['secret'] }}</code>
		</div>
		<div class="df aic g-2">
			<div class="field">
				<div class="field-item">
					<input type="text" id="two-factor-code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="{!! t_attr( '6-digit code' ) !!}">
				</div>
			</div>
			<button class="btn btn--outline" type="button" @click="$ajax.post('user/two-factor-enable', {code: document.getElementById('two-factor-code').value})">
				<i class="ph ph-check"></i> {!! t( 'Turn On' ) !!}
			</button>
		</div>
	</div>
@elseif($isEnabled)
	<div class="dg g-2">
		<div class="df aic g-1"><i class="ph ph-check-circle"></i> {!! t( 'Two-factor authentication is on: signing in with a password or a provider asks for a code from your app.' ) !!}</div>
		<div class="df fw g-2">
			<button class="btn btn--outline" type="button" @click="$ajax.post('user/two-factor-codes', {password: confirmPassword})">
				<i class="ph ph-arrows-clockwise"></i> {!! t( 'New recovery codes' ) !!}
			</button>
			@if(!App\Api\User\TwoFactor::isRequired($user))
				<button class="btn btn--outline t-red" type="button" @click="$ajax.post('user/two-factor-disable', {password: confirmPassword})">
					<i class="ph ph-power"></i> {!! t( 'Turn Off' ) !!}
				</button>
			@endif
		</div>
	</div>
@else
	<div class="dg g-2">
		<div>{!! t( 'Ask for a code from an authenticator app after the password, so a stolen password alone is not enough. Passkeys need no code.' ) !!}</div>
		<div>
			<button class="btn btn--outline" type="button" @click="$ajax.post('user/two-factor-setup', {password: confirmPassword})">
				<i class="ph ph-device-mobile"></i> {!! t( 'Set Up' ) !!}
			</button>
		</div>
	</div>
@endif
