<?php
/**
 * Passkey card of the profile, also returned by the "user/passkey-create" API.
 *
 * @var array{id: int, name: string, backed_up: bool, created_at: string, used_at: ?string} $passkey
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$date = new IntlDateFormatter( Expansa\Facades\I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT );
?>
<div class="p-4 df fdr aic g-4 card card-border" id="passkey-{{ $passkey['id'] }}">
	<i class="ph ph-fingerprint fs-24"></i>
	<div class="dg g-1">
		<h6 class="fs-15">{{ $passkey['name'] }}</h6>
		<div class="fs-12 t-muted lh-xs">
			{!! t( 'Added :date', $date->format( strtotime( $passkey['created_at'] ) ) ) !!}
			@if($passkey['used_at'])
				· {!! t( 'Last used :date', $date->format( strtotime( $passkey['used_at'] ) ) ) !!}
			@endif
			@if($passkey['backed_up'])
				· {!! t( 'Synced' ) !!}
			@endif
		</div>
	</div>
	<div class="ml-auto">
		<button class="btn btn--sm btn--icon t-red" type="button" title="{!! t_attr('Remove') !!}" @click="$ajax.post('user/passkey-delete', {id: {{ $passkey['id'] }}})">
			<i class="ph ph-trash"></i>
		</button>
	</div>
</div>
