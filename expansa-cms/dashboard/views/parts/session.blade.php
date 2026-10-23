<?php
/**
 * Signed-in device of the profile.
 *
 * @var App\Api\User\Values\Session $session
 * @var App\Support\Site $site Settings of the site: name, language, charset, addresses, version; shared with every view.
 */
defined('EX_PATH') || exit;
?>
<div class="p-4 df fdr aic g-4 card card-border" id="session-{{ $session->id }}" @if(!$session->current) data-session-other @endif>
	<i class="ph ph-{{ $session->mobile ? 'device-mobile' : 'desktop' }} fs-24"></i>
	<div class="dg g-1">
		<h6 class="fs-15">
			{{ $session->device }}
			@if($session->current)
				<span class="badge badge--green-lt">{!! t( 'This Device' ) !!}</span>
			@endif
		</h6>
		<div class="fs-12 t-muted lh-xs">
			{{ $session->ip }} · {!! t( 'Last active :date', $session->active ) !!}
		</div>
	</div>
	@if(!$session->current)
		<div class="ml-auto">
			<button class="btn btn--sm btn--icon t-red" type="button" title="{!! t_attr('Sign Out') !!}" @click="$ajax.post('user/session-delete', {id: {{ $session->id }}, password: confirmPassword})">
				<i class="ph ph-sign-out"></i>
			</button>
		</div>
	@endif
</div>
