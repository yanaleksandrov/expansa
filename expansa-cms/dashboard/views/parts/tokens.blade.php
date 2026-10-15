<?php
/**
 * Personal API tokens of the profile, also returned by the "user/token-create" API.
 *
 * @var array<int, array{id: int, name: string, prefix: string, scopes: string[], last_used_at: ?string, expires_at: ?string}> $tokens
 */
defined('EX_PATH') || exit;
?>
@foreach($tokens as $token)
	<div class="p-4 df fdr aic g-4 card card-border" id="token-{{ $token['id'] }}">
		<i class="ph ph-key fs-24"></i>
		<div class="dg g-1">
			<h6 class="fs-15">{{ $token['name'] }} <code class="fs-12">exp_{{ $token['prefix'] }}_…</code></h6>
			<div class="fs-12 t-muted lh-xs">
				{{ implode(', ', $token['scopes']) }} ·
				{{ $token['last_used_at'] ? t('Last used :date', $token['last_used_at']) : t('Never used') }} ·
				{{ $token['expires_at'] ? t('Expires :date', $token['expires_at']) : t('Does not expire') }}
			</div>
		</div>
		<div class="ml-auto">
			<button class="btn btn--sm btn--icon t-red" type="button" title="{!! t_attr('Revoke') !!}" @click="$ajax.post('user/token-delete', {id: {{ $token['id'] }}, password: confirmPassword})">
				<i class="ph ph-trash"></i>
			</button>
		</div>
	</div>
@endforeach
