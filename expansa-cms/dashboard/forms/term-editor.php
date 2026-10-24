<?php

use App\Dashboard\Pages\Post;
use App\Dashboard\Pages\Term as Page;
use App\Dashboard\Values\Crumb;
use App\Models\Term;
use App\Post\Taxonomy;
use App\Support\Activity;
use App\Support\Dates;
use App\Support\Permalinks;
use App\Support\Values\Line;
use Expansa\Builders\Form;

$term      = Page::term(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0, '');
$taxonomy  = Taxonomy::get($term->taxonomy);
$type      = (string) filter_input(INPUT_GET, 'type');
$list      = url('dashboard/' . $taxonomy->url($type));
$parents   = $taxonomy->hierarchical ? Page::parents($term) : [];
$isDefault = Term::defaultId($taxonomy->key) === $term->id;

// the URL of the term and of each parent, the base of the slug by the permalink structure;
// the paths of the term and the parents are read together
$paths  = $taxonomy->public ? Term::paths($taxonomy->key, [$term->id, ...array_keys($parents)]) : [];
$url    = $taxonomy->public ? $term->url($paths[$term->id] ?? '') : '';
$around = $taxonomy->public ? Permalinks::termAround($paths[$term->parentId] ?? '', $taxonomy->key) : ['', ''];
$bases  = [];
foreach ($taxonomy->public ? $parents : [] as $id => $name) {
    $bases[$id] = Permalinks::termAround($paths[$id] ?? '', $taxonomy->key)[0];
}

// the select of the parent aside is written by hand, not by Form::view(): its script is connected here
Form::assets('select', 'select');

/**
 * Term editor, laid out as the post editor: the bar with the way back and the public page over the tabs,
 * the name, slug and description in the main tab, the parent and details aside.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'term-editor',
    [
        'class'           => 'tab post-editor',
        'u-data'          => 'tab as tab',
        '@load'           => '$dirty.watch($el)',
        '@submit.prevent' => '$ajax.post("terms/update", "", () => $dirty.remove($el))',
    ],
    [
        [
            'type'       => 'hidden',
            'name'       => 'id',
            'attributes' => ['type' => 'hidden', 'name' => 'id', 'value' => $term->id],
        ],
        [
            'type'       => 'hidden',
            'name'       => 'type',
            'attributes' => ['type' => 'hidden', 'name' => 'type', 'value' => $type],
        ],
        [
            'name'     => 'header',
            'type'     => 'custom',
            'callback' => function () use ($term, $taxonomy, $list, $url, $isDefault) {
                ?>
                <div class="post-editor-head">
                    <a class="btn btn--icon btn--xs" href="<?php echo $list; ?>" title="<?php echo t_attr('Back to :items', $taxonomy->labelNamePlural); ?>">
                        <i class="ph ph-arrow-left"></i>
                    </a>
                    <?php
                    echo view('parts/breadcrumbs', [
                        'items' => [
                            new Crumb($taxonomy->labelNamePlural, $list),
                            new Crumb(
                                label: $term->name,
                                url: $url,
                                attributes: $url !== '' ? [
                                    'target'             => '_blank',
                                    'title'              => t('View'),
                                    '@slug-url.document' => '$el.href = $event.detail',
                                ] : [],
                            ),
                        ],
                    ]);
                    ?>
                    <?php if ($isDefault) : ?>
                        <span class="badge t-blue"><?php echo t('Default'); ?></span>
                    <?php endif; ?>
                </div>
                <?php
            },
        ],
        [
            'type'        => 'tab',
            'label'       => t('Main'),
            'name'        => 'main',
            'caption'     => '',
            'description' => '',
            'icon'        => 'ph ph-tag',
            'fields'      => [
                [
                    'type'        => 'text',
                    'name'        => 'name',
                    'error'       => 'name',
                    'label'       => '',
                    'class'       => 'field field--raised field--xl',
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
                        'u-prop'      => 'name',
                        'value'       => $term->name,
                        'placeholder' => t('Name'),
                        'aria-label'  => t('Name'),
                        'required'    => true,
                    ],
                ],
                [
                    'type'        => 'slug',
                    'name'        => 'slug',
                    'error'       => 'slug',
                    'label'       => '',
                    'class'       => 'field post-editor-slug',
                    'label_class' => '',
                    'instruction' => '',
                    'tooltip'     => t('The address of the term: letters, numbers and hyphens. Leave it empty to make it from the name.'),
                    'conditions'  => [],
                    'from'        => 'name',
                    'base'        => $around[0],
                    'suffix'      => $around[1],
                    'parent'      => $taxonomy->public && $parents ? 'parent_id' : '',
                    'bases'       => $bases,
                    'attributes'  => [
                        'value'       => $term->slug,
                        'placeholder' => t('slug'),
                        'aria-label'  => t('Slug'),
                    ],
                ],
                [
                    'type'   => 'group',
                    'name'   => 'main',
                    'label'  => '',
                    'layout' => 'plain',
                    'class'  => 'post-editor-main',
                    'fields' => [
                        [
                            'type'        => 'textarea',
                            'name'        => 'description',
                            'error'       => 'description',
                            'label'       => t('Description'),
                            'class'       => '',
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
                                'rows'   => '8',
                                'u-prop' => 'description',
                                'value'  => $term->description,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'        => 'tab',
            'label'       => t('Activity'),
            'name'        => 'activity',
            'caption'     => '',
            'description' => '',
            'icon'        => 'ph ph-clock-counter-clockwise',
            'fields'      => [
                [
                    'name'     => 'activity',
                    'type'     => 'custom',
                    'callback' => function () use ($term) {
                        $lines = [
                            new Line(
                                icon: 'ph ph-plus',
                                text: t('Created'),
                                at: $term->createdAt,
                            ),
                        ];
                        echo view('parts/activity', ['lines' => Activity::timeline($term->table, $term->id, $lines)]);
                    },
                ],
            ],
        ],
        [
            'type'   => 'group',
            'name'   => 'side',
            'label'  => '',
            'layout' => 'plain',
            'class'  => 'post-editor-side',
            'fields' => [
                [
                    'name'     => 'properties',
                    'type'     => 'custom',
                    'callback' => function () use ($term, $parents) {
                        $facts = [
                            ['ph ph-files', t('Count'), (string) $term->count],
                            ['ph ph-calendar-plus', t('Created'), Dates::format($term->createdAt)],
                            ['ph ph-clock-clockwise', t('Updated'), Dates::format($term->updatedAt)],
                            ['ph ph-hash', t('ID'), (string) $term->id],
                        ];
                        ?>
                        <h4 class="post-editor-eyebrow"><?php echo t('Details'); ?></h4>
                        <dl class="post-editor-props">
                            <?php if ($parents) : ?>
                                <dt><i class="ph ph-tree-structure"></i><?php echo t('Parent'); ?></dt>
                                <dd>
                                    <select class="field field--xs" name="parent_id" aria-label="<?php echo t_attr('Parent'); ?>" u-select="<?php echo count($parents) > Post::OPTIONS_LIMIT ? '{showSearch: true}' : '{}'; ?>">
                                        <?php foreach ($parents as $id => $name) : ?>
                                            <option value="<?php echo $id; ?>"<?php echo $id === $term->parentId ? ' selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </dd>
                            <?php endif; ?>
                            <?php foreach ($facts as [$icon, $label, $fact]) : ?>
                                <dt><i class="<?php echo $icon; ?>"></i><?php echo $label; ?></dt>
                                <dd><span class="post-editor-pill<?php echo $fact === '' ? ' is-empty' : ''; ?>"><?php echo $fact !== '' ? htmlspecialchars($fact) : '—'; ?></span></dd>
                            <?php endforeach; ?>
                        </dl>
                        <?php
                    },
                ],
                ...($isDefault ? [] : [
                    [
                        'name'     => 'manage',
                        'type'     => 'custom',
                        'callback' => function () use ($term, $taxonomy, $type) {
                            $item = htmlspecialchars(json_encode(['id' => $term->id, 'type' => $type]));
                            ?>
                            <div class="post-editor-actions">
                                <?php if ($taxonomy->hierarchical) : ?>
                                    <button class="btn btn--sm btn--outline" type="button" @click="$ajax.post('terms/set-default', {id: <?php echo $term->id; ?>})">
                                        <i class="ph ph-push-pin"></i> <?php echo t('Make Default'); ?>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn--sm btn--outline t-red" type="button" @click="$dialog.confirm(texts.deleteItem, { action: texts.deletePermanently, danger: true }).then(ok => ok && $ajax.post('terms/delete', <?php echo $item; ?>))">
                                    <i class="ph ph-trash"></i> <?php echo t('Delete'); ?>
                                </button>
                            </div>
                            <?php
                        },
                    ],
                ]),
            ],
        ],
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <div class="expansa-form-actions">
                    <div class="expansa-form-actions-caption">
                        <i class="ph ph-warning-circle"></i> <?php echo t('Unsaved changes'); ?>
                    </div>
                    <div class="expansa-form-actions-buttons">
                        <button class="btn t-red" type="reset"><?php echo t('Discard'); ?></button>
                        <button class="btn t-green" type="submit"><?php echo t('Save'); ?></button>
                    </div>
                </div>
                <?php
            },
        ],
    ]
);
