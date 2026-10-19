<?php

use Expansa\Builders\Form;
use Expansa\Facades\Safe;

/**
 * Bulk actions of the selected rows of a table, see Table::bulk(): the action is posted to `endpoint`
 * with the IDs of the checked rows and the `data` fields; the `confirm` actions ask first, the `password`
 * actions show the field of the current password.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/bulk.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$endpoint, $actions, $confirm, $password, $data] = Safe::data($__data ?? [], [
    'endpoint' => 'trim',
    'actions'  => 'array',
    'confirm'  => 'array',
    'password' => 'array',
    'data'     => 'array',
])->values();

$ids    = '[...$el.closest(".table").querySelectorAll("[u-bind~=item]:checked")].map(input => input.value).join(",")';
$submit = sprintf('$ajax.post(%s, {ids: %s})', json_encode($endpoint), $ids);
if ($confirm) {
    $submit = sprintf('(!%s.includes(action) || confirm(texts.deleteItems)) && %s', json_encode(array_values($confirm)), $submit);
}
?>
<form class="df g-1" method="POST" u-data="{action: ''}" @submit.prevent="{{ $submit }}">
    @foreach ($data as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <?php
    echo Form::view('select', [
        'type'       => 'select',
        'name'       => 'action',
        'error'      => 'action',
        'class'      => 'field field--xs field--raised',
        'attributes' => [
            'u-prop'   => 'action',
            'u-select' => '',
        ],
        'options'    => ['' => t('Bulk Actions'), ...$actions],
    ]);
    ?>
    @if ($password)
        <div class="field field--xs field--raised" u-show="{{ json_encode(array_values($password)) }}.includes(action)" hidden>
            <div class="field-item">
                <input type="password" name="password" autocomplete="current-password" placeholder="{{ t_attr('Your current password') }}">
            </div>
        </div>
    @endif
    <button class="btn btn--xs btn--secondary" type="submit" :disabled="!action">
        <i class="ph ph-paper-plane-tilt"></i> {{ t('Apply') }}
    </button>
</form>
