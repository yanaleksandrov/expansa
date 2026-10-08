<?php
/**
 * Bar shown while an administrator is signed in as another user, with the way back.
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$impersonator = App\Api\User\Admin::getImpersonator();
$user         = App\Models\User::current();
?>
@if($impersonator && $user)
	<div class="df aic jcc g-2 p-2 fs-13 t-red">
		<i class="ph ph-user-switch"></i>
		{{ t('You are signed in as :user.', $user->showname ?: $user->login) }}
		<button class="btn btn--xs btn--outline" type="button" @click="$ajax.post('user/stop-impersonating')">
			{{ t('Back to :admin', $impersonator->showname ?: $impersonator->login) }}
		</button>
	</div>
@endif
