<?php

use Expansa\Builders\Form\Data;
use Expansa\Facades\Safe;

/**
 * Form tab menu.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/tab-menu.php
 *
 * @var Data $field
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$tabs = [];
$menu = '';
foreach ($field->fields as $tab) {
    if (($tab['type'] ?? '') !== 'tab') {
        continue;
    }

    // the first tab with a menu class gives it to the whole menu
    $menu = $menu ?: Safe::class($tab['class_menu'] ?? '');

    $tabs[] = [
        'prop'  => Safe::prop($tab['name'] ?? '') ?: 'tab',
        'label' => Safe::trim($tab['label'] ?? ''),
        'icon'  => Safe::attribute($tab['icon'] ?? ''),
        'class' => Safe::class(trim(($tab['class_button'] ?? '') . (empty($tab['active']) ? '' : ' is-active'))),
    ];
}

if ($tabs === []) {
    return;
}
?>
<ul class="{{ trim('tab__nav ' . $menu) }}" u-sticky>
	@foreach($tabs as $tab)
		<li class="{{ trim('tab__title ' . $tab['class']) }}" u-bind="tabButton('{{ $tab['prop'] }}')">
			@if($tab['icon'])
				<i class="{!! $tab['icon'] !!}"></i>
			@endif
			{!! $tab['label'] !!}
		</li>
	@endforeach
</ul>
