<?php
/**
 * Signed-in device of the profile.
 *
 * @var array{id: int, device: string, ip: string, created_at: string, used_at: string, current: bool} $session
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

$date = new IntlDateFormatter( Expansa\Facades\I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT );
?>
<div class="p-4 df fdr aic g-4 card card-border" id="session-{{ $session['id'] }}" @if(!$session['current']) data-session-other @endif>
	<i class="ph ph-{{ preg_match('/iPhone|Android/', $session['device']) ? 'device-mobile' : 'desktop' }} fs-24"></i>
	<div class="dg g-1">
		<h6 class="fs-15">
			{{ $session['device'] }}
			@if($session['current'])
				<span class="badge bg-green">{!! t( 'This device' ) !!}</span>
			@endif
		</h6>
		<div class="fs-12 t-muted lh-xs">
			{{ $session['ip'] }} · {!! t( 'Last active :date', $date->format( strtotime( $session['used_at'] ?: $session['created_at'] ) ) ) !!}
		</div>
	</div>
	@if(!$session['current'])
		<div class="ml-auto">
			<button class="btn btn--sm btn--icon t-red" type="button" title="{!! t_attr('Sign out') !!}" @click="$ajax.post('user/session-delete', {id: {{ $session['id'] }}})">
				<i class="ph ph-sign-out"></i>
			</button>
		</div>
	@endif
</div>
