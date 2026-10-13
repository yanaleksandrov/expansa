<?php

use App\Models\Comment;
use App\Tables\Comments;
use Expansa\Builders\Form;
use Expansa\Facades\Safe;

$counts   = Comments::counts();
$statuses = ['' => t('All (:count)', array_sum($counts))];
foreach (Comment::statuses() as $status => $label) {
    $statuses[$status] = t(':status (:count)', $label, $counts[$status] ?? 0);
}

$search = Safe::text($_GET['s'] ?? '');
$status = Safe::text($_GET['status'] ?? '');

/**
 * Filter of the comments table: the status and a search, sent as the query of the page.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'comments-filter',
    [
        'class'  => 'table-filter',
        'method' => 'GET',
        'action' => url('dashboard/edit'),
    ],
    [
        [
            'type'       => 'hidden',
            'name'       => 'table',
            'attributes' => ['type' => 'hidden', 'name' => 'table', 'value' => 'comments'],
        ],
        [
            'type'       => 'hidden',
            'name'       => 'order',
            'attributes' => ['type' => 'hidden', 'name' => 'order', 'value' => ($_GET['order'] ?? '') === 'asc' ? 'asc' : ''],
        ],
        [
            'type'        => 'select',
            'name'        => 'status',
            'error'       => 'status',
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
                'name'    => 'status',
                'value'   => $status,
                '@change' => '$event.isTrusted && $el.form.requestSubmit()',
            ],
            'options'     => $statuses,
        ],
        [
            'type'        => 'search',
            'name'        => 's',
            'error'       => 's',
            'label'       => '',
            'class'       => 'field field--raised',
            'label_class' => '',
            'reset'       => 0,
            'before'      => '<i class="ph ph-magnifying-glass"></i>',
            'after'       => '',
            'instruction' => '',
            'tooltip'     => '',
            'copy'        => 0,
            'validator'   => '',
            'conditions'  => [],
            'attributes'  => [
                'type'        => 'search',
                'name'        => 's',
                'value'       => $search,
                'placeholder' => t('Search comments'),
            ],
        ],
        [
            'type'     => 'custom',
            'callback' => function () use ($search, $status) {
                ?>
                <button class="btn btn--outline" type="submit"><?php echo t('Apply Filter'); ?></button>
                <?php if ($search !== '' || $status !== '') : ?>
                    <a class="btn t-red" href="<?php echo url('dashboard/edit?table=comments'); ?>"><?php echo t('Reset'); ?></a>
                <?php endif; ?>
                <?php
            },
        ],
    ]
);
