<?php
/**
 * Connected sign-in provider account of the profile.
 *
 * @var array{id: int, provider: string, email: string, created_at: string} $identity
 * @var App\Support\Site $site Settings of the site: name, language, charset, addresses, version; shared with every view.
 */
defined('EX_PATH') || exit;
?>
<div class="p-4 df fdr aic g-4 card card-border" id="identity-{{ $identity['id'] }}">
	<i class="ph ph-{{ $identity['provider'] }}-logo fs-24"></i>
	<div class="dg g-1">
		<h6 class="fs-15">{{ App\Api\User\Identities::label( $identity['provider'] ) }}</h6>
		<div class="fs-12 t-muted lh-xs">
			@if($identity['email'])
				{{ $identity['email'] }} ·
			@endif
			{!! t( 'Connected :date', App\Support\Dates::format( $identity['created_at'], false ) ) !!}
		</div>
	</div>
	<div class="ml-auto">
		<button class="btn btn--sm btn--icon t-red" type="button" title="{!! t_attr('Disconnect') !!}" @click="$ajax.post('user/identity-delete', {id: {{ $identity['id'] }}, password: confirmPassword})">
			<i class="ph ph-link-break"></i>
		</button>
	</div>
</div>
