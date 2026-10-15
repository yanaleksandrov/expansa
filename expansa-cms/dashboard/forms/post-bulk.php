<?php

use App\Post\Type;
use Expansa\Builders\Form;

$type    = Type::get((string) filter_input(INPUT_GET, 'table') ?: 'pages');
$isTrash = filter_input(INPUT_GET, 'view') === 'trash';
$actions = match (true) {
    $type === null  => [],
    $isTrash        => [
        'restore' => t('Restore'),
        'delete'  => t('Delete Permanently'),
    ],
    $type->canTrash => [
        'duplicate' => t('Duplicate'),
        'trash'     => t('Move to Trash'),
    ],
    default         => [
        'duplicate' => t('Duplicate'),
        'delete'    => t('Delete Permanently'),
    ],
};

/**
 * Bulk actions of a post type table: duplicate and move to the trash, or restore and delete
 * permanently in the trash; a type without a trash deletes at once.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'post-bulk',
    [
        'class'           => 'df g-1',
        'u-data'          => '{action: ""}',
        '@submit.prevent' => sprintf(
            '(action !== "delete" || confirm(%s)) && $ajax.post("post/bulk", {ids: [...$el.closest(".table").querySelectorAll("[u-bind~=item]:checked")].map(input => input.value).join(",")})',
            json_encode(t('Delete the selected items permanently?')),
        ),
    ],
    [
        [
            'type'       => 'hidden',
            'name'       => 'post-type',
            'attributes' => ['type' => 'hidden', 'name' => 'post-type', 'value' => $type->key ?? ''],
        ],
        [
            'type'        => 'select',
            'name'        => 'action',
            'error'       => 'action',
            'label'       => '',
            'class'       => 'field field--raised',
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
                <button class="btn btn--secondary" type="submit" :disabled="!action">
                    <i class="ph ph-paper-plane-tilt"></i> <?php echo t('Apply'); ?>
                </button>
                <?php
            },
        ],
    ]
);
