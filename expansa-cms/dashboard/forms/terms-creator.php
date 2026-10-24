<?php

use App\Dashboard\Pages\Term as Page;
use App\Post\Taxonomy;
use Expansa\Builders\Form;

$term     = Page::term(0, (string) filter_input(INPUT_GET, 'taxonomy'));
$taxonomy = Taxonomy::get($term->taxonomy);
$type     = (string) filter_input(INPUT_GET, 'type');

/**
 * Form of a new term beside the list of the terms of a taxonomy; a saved term opens in the `term-editor`.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'terms-creator',
    [
        'class'           => 'dg g-6',
        '@submit.prevent' => '$ajax.post("terms/create")',
    ],
    [
        [
            'type'       => 'hidden',
            'name'       => 'taxonomy',
            'attributes' => ['type' => 'hidden', 'name' => 'taxonomy', 'value' => $taxonomy->key],
        ],
        [
            'type'       => 'hidden',
            'name'       => 'type',
            'attributes' => ['type' => 'hidden', 'name' => 'type', 'value' => $type],
        ],
        [
            'name'     => 'header',
            'type'     => 'custom',
            'callback' => function () use ($taxonomy) {
                ?>
                <h6><?php echo htmlspecialchars($taxonomy->labelAdd); ?></h6>
                <?php
            },
        ],
        [
            'type'        => 'text',
            'name'        => 'name',
            'error'       => 'name',
            'label'       => t('Name'),
            'class'       => 'field field--raised',
            'label_class' => '',
            'reset'       => 0,
            'before'      => '',
            'after'       => '',
            'instruction' => t('The name is how it appears on your site.'),
            'tooltip'     => '',
            'copy'        => 0,
            'validator'   => '',
            'conditions'  => [],
            'attributes'  => [
                'u-prop'   => 'name',
                'value'    => $term->name,
                'required' => true,
            ],
        ],
        [
            'type'        => 'text',
            'name'        => 'slug',
            'error'       => 'slug',
            'label'       => t('Slug'),
            'class'       => 'field field--raised',
            'label_class' => '',
            'reset'       => 0,
            'before'      => '',
            'after'       => '',
            'instruction' => t('The address of the term: letters, numbers and hyphens. Leave it empty to make it from the name.'),
            'tooltip'     => '',
            'copy'        => 0,
            'validator'   => '',
            'conditions'  => [],
            'attributes'  => [
                'u-prop' => 'slug',
                'value'  => $term->slug,
            ],
        ],
        ...($taxonomy->hierarchical ? [
            [
                'type'        => 'select',
                'name'        => 'parent_id',
                'error'       => 'parent_id',
                'label'       => t('Parent'),
                'class'       => 'field field--raised',
                'label_class' => '',
                'reset'       => 0,
                'before'      => '',
                'after'       => '',
                'instruction' => t('Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.'),
                'tooltip'     => '',
                'copy'        => 0,
                'validator'   => '',
                'conditions'  => [],
                'options'     => Page::parents($term),
                'attributes'  => [
                    'u-prop' => 'parent_id',
                    'value'  => (string) $term->parentId,
                ],
            ],
        ] : []),
        [
            'type'        => 'textarea',
            'name'        => 'description',
            'error'       => 'description',
            'label'       => t('Description'),
            'class'       => 'field field--raised',
            'label_class' => '',
            'reset'       => 0,
            'before'      => '',
            'after'       => '',
            'instruction' => t('The description is not prominent by default; however, some themes may show it.'),
            'tooltip'     => '',
            'copy'        => 0,
            'validator'   => '',
            'conditions'  => [],
            'attributes'  => [
                'u-prop' => 'description',
                'value'  => $term->description,
            ],
        ],
        [
            'name'     => 'actions',
            'type'     => 'custom',
            'callback' => function () use ($taxonomy) {
                ?>
                <div class="df aic g-2">
                    <button class="btn btn--primary" type="submit"><?php echo htmlspecialchars($taxonomy->labelAdd); ?></button>
                </div>
                <?php
            },
        ],
    ]
);
