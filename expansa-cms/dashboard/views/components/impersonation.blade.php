<?php
/**
 * Bar shown while an administrator is signed in as another user, with the way back.
 *
 * @var App\Models\User|null $viewer       User who views the page, shared with dashboard views.
 * @var App\Models\User|null $impersonator Administrator signed in as the viewer, shared with dashboard views.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
@if($impersonator && $viewer)
	<div class="df aic jcc g-2 p-2 fs-13 t-red">
		<i class="ph ph-user-switch"></i>
		{{ t('You are signed in as :user.', $viewer->showname ?: $viewer->login) }}
		<button class="btn btn--xs btn--outline" type="button" @click="$ajax.post('user/stop-impersonating')">
			{{ t('Back to :admin', $impersonator->showname ?: $impersonator->login) }}
		</button>
	</div>
@endif
