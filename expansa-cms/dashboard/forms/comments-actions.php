<?php

use App\Models\Comment;
use Expansa\Builders\Form;

$actions = filter_input(INPUT_GET, 'view') === 'trash'
    ? [
        'restore'      => t('Restore'),
        'force-delete' => t('Delete Permanently'),
    ]
    : [
        ...array_map(fn (string $label) => t('Mark as :status', $label), Comment::statuses()),
        'delete' => t('Delete'),
    ];

/**
 * Bulk actions of the comments table: set a status or move the selected rows to the trash,
 * restore or delete them permanently in the trash.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'comments-actions',
    [
        'class'           => 'df g-1',
        'u-data'          => '{action: ""}',
        '@submit.prevent' => '(action !== "force-delete" || confirm(texts.deleteItems)) && $ajax.post("comments/bulk", {ids: [...$el.closest(".table").querySelectorAll("[u-bind~=item]:checked")].map(input => input.value).join(",")})',
    ],
    [
        [
            'type'        => 'select',
            'name'        => 'action',
            'error'       => 'action',
            'label'       => '',
            'class'       => 'field field--xs field--raised',
            'label_class' => '',
            'reset'       => 0,
            'before'      => '',
            'after'       => '',
            'instruction' => '',
            'tooltip'     => '',
            'copy'        => 0,
            'validator'   => '',
            'conditions'  => [],
            'attributes'  => [
                'u-prop' => 'action',
            ],
            'options'     => ['' => t('Bulk Actions'), ...$actions],
        ],
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <button class="btn btn--xs btn--secondary" type="submit" :disabled="!action">
                    <i class="ph ph-paper-plane-tilt"></i> <?php echo t('Apply'); ?>
                </button>
                <?php
            },
        ],
    ]
);
