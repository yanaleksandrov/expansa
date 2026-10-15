<?php

use App\Dashboard\Pages\Post as Page;
use App\Post\Status;
use App\Post\Type;
use Expansa\Builders\Form;
use Expansa\Facades\I18n;

$post = Page::post((string) filter_input(INPUT_GET, 'type') ?: 'pages', filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
$type = Type::get($post->type);
$date = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);

/**
 * Post editor: the title, slug and content in the main card, the publishing options and details aside.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'post-editor',
    [
        'class'           => 'post-editor',
        '@load'           => '$dirty.watch($el)',
        '@submit.prevent' => sprintf('$ajax.post("%s", "", () => $dirty.remove($el))', $post->id === 0 ? 'post/create' : 'post/update'),
    ],
    [
        [
            'type'       => 'hidden',
            'name'       => 'id',
            'attributes' => ['type' => 'hidden', 'name' => 'id', 'value' => $post->id],
        ],
        [
            'type'       => 'hidden',
            'name'       => 'post-type',
            'attributes' => ['type' => 'hidden', 'name' => 'post-type', 'value' => $type->key],
        ],
		[
			'type'          => 'tab',
			'label'         => t('Main'),
			'name'          => 'main',
			'caption'       => '',
			'description'   => '',
			'icon'          => 'ph ph-chat-text',
			'class_menu'    => '',
			'class_button'  => '',
			'class_content' => '',
			'fields'        => [],
		],
        [
            'type'          => 'group',
            'name'          => 'main',
            'label'         => '',
            'class'         => 'post-editor-main',
            'label_class'   => '',
            'content_class' => 'dg',
            'fields'        => [
                [
                    'name'     => 'header',
                    'type'     => 'custom',
                    'callback' => function () use ($post, $type, $date) {
                        $author  = Page::author($post);
                        $updated = $post->updatedAt ? $date->format(strtotime($post->updatedAt)) : '';
                        ?>
                        <h5 class="post-editor-title">
                            <a class="btn btn--icon btn--sm" href="<?php echo url('dashboard/edit?table=' . $type->key); ?>" title="<?php echo t_attr('Back to :items', $type->labelNamePlural); ?>">
                                <i class="ph ph-arrow-left"></i>
                            </a>
                            <span class="fw-600 mr-auto"><?php echo $post->id === 0 ? $type->labelAdd : $type->labelEdit; ?></span>
	                        <?php
	                        echo Form::view('input', [
		                        'type'       => 'input',
		                        'name'       => 'terminal',
		                        'class'      => 'field field--plain',
		                        'before'     => '<i class="ph ph-terminal"></i>',
		                        'attributes' => [
				                        'name'         => 's',
				                        'placeholder'  => t('Describe what you want...'),
				                        'u-noautofill' => 'terminal',
		                        ],
	                        ]);
	                        ?>
	                        <?php if ($post->isTrashed()) : ?>
                                <span class="badge t-red"><?php echo t('In the trash'); ?></span>
                            <?php endif; ?>
                            <?php if ($type->public && $post->slug !== '') : ?>
                                <a class="btn btn--sm btn--outline" href="<?php echo url($post->slug); ?>" target="_blank"><?php echo $type->labelView; ?></a>
                            <?php endif; ?>
                            <button class="btn btn--sm btn--primary" type="submit"><?php echo $type->labelSave; ?></button>
                        </h5>
                        <?php if ($updated !== '') : ?>
                            <div class="post-editor-description">
                                <?php echo $author !== '' ? t('Updated :date, author :name', $updated, htmlspecialchars($author)) : t('Updated :date', $updated); ?>
                            </div>
                        <?php endif; ?>
                        <?php
                    },
                ],
                [
                    'type'          => 'group',
                    'name'          => 'content',
                    'label'         => '',
                    'class'         => 'post-editor-card',
                    'label_class'   => '',
                    'content_class' => 'dg g-5',
                    'fields'        => [
                        [
                            'type'        => 'text',
                            'name'        => 'title',
                            'error'       => 'title',
                            'label'       => t('Title'),
                            'class'       => '',
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
                                'u-prop'   => 'title',
                                'value'    => $post->title,
                                'required' => true,
                            ],
                        ],
                        ...($type->public ? [
                            [
                                'type'        => 'text',
                                'name'        => 'slug',
                                'error'       => 'slug',
                                'label'       => t('Slug'),
                                'class'       => '',
                                'label_class' => '',
                                'reset'       => 0,
                                'before'      => '<span class="t-muted">' . htmlspecialchars(rtrim(url(), '/')) . '/</span>',
                                'after'       => '',
                                'instruction' => t('The URL-friendly version of the title: lowercase letters, numbers and hyphens. Leave it empty to make it from the title.'),
                                'tooltip'     => '',
                                'copy'        => 0,
                                'validator'   => '',
                                'conditions'  => [],
                                'attributes'  => [
                                    'u-prop' => 'slug',
                                    'value'  => $post->slug,
                                ],
                            ],
                        ] : []),
                        [
                            'name'     => 'content',
                            'type'     => 'custom',
                            'callback' => function () use ($post) {
                                ?>
                                <div class="field">
                                    <div class="field-label"><?php echo t('Content'); ?></div>
                                    <label class="field-item">
                                        <textarea class="post-editor-text" name="content" u-prop="content" rows="16"><?php echo htmlspecialchars($post->content); ?></textarea>
                                    </label>
                                    <div class="field-instruction"><?php echo t('HTML is allowed: unsafe tags and attributes are removed on saving.'); ?></div>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'          => 'group',
            'name'          => 'side',
            'label'         => '',
            'class'         => 'post-editor-side',
            'label_class'   => '',
            'content_class' => 'post-editor-card',
            'fields'        => [
                [
                    'name'     => 'properties',
                    'type'     => 'custom',
                    'callback' => function () use ($post, $type, $date) {
                        $author   = Page::author($post);
                        $statuses = Status::getStatuses();
                        $parents  = $type->hierarchical ? Page::parents($post) : [];
                        $options  = function (array $options, int|string $value): void {
                            foreach ($options as $key => $label) {
                                printf('<option value="%s"%s>%s</option>', htmlspecialchars((string) $key), (string) $key === (string) $value ? ' selected' : '', htmlspecialchars($label));
                            }
                        };
                        $facts    = $post->id === 0 ? [] : [
                            ['ph ph-calendar-plus', t('Created'), $post->createdAt ? $date->format(strtotime($post->createdAt)) : ''],
                            ['ph ph-clock-clockwise', t('Updated'), $post->updatedAt ? $date->format(strtotime($post->updatedAt)) : ''],
                            ['ph ph-chat-circle-text', t('Comments'), (string) $post->comments],
                            ['ph ph-eye', t('Views'), (string) $post->views],
                            ['ph ph-hash', t('ID'), (string) $post->id],
                        ];
                        ?>
                        <dl class="post-editor-props" u-data="{status: <?php echo htmlspecialchars(json_encode($post->status)); ?>}">
                            <dt><i class="ph ph-circle-dashed"></i><?php echo t('Status'); ?></dt>
                            <dd>
                                <select class="post-editor-pill" name="status" u-prop="status" aria-label="<?php echo t_attr('Status'); ?>">
                                    <?php $options($statuses, $post->status); ?>
                                </select>
                            </dd>
                            <dt u-show="status === 'protected'"><i class="ph ph-lock-simple"></i><?php echo t('Password'); ?></dt>
                            <dd u-show="status === 'protected'">
                                <input class="post-editor-pill" type="text" name="password" value="<?php echo htmlspecialchars($post->password); ?>" placeholder="<?php echo t_attr('Set password'); ?>" autocomplete="off" aria-label="<?php echo t_attr('Password'); ?>">
                            </dd>
                            <?php if ($parents) : ?>
                                <dt><i class="ph ph-tree-structure"></i><?php echo t('Parent'); ?></dt>
                                <dd>
                                    <select class="post-editor-pill<?php echo $post->parentId === 0 ? ' is-empty' : ''; ?>" name="parent_id" aria-label="<?php echo t_attr('Parent'); ?>">
                                        <?php $options($parents, $post->parentId); ?>
                                    </select>
                                </dd>
                            <?php endif; ?>
                            <dt><i class="ph ph-chats"></i><?php echo t('Discussion'); ?></dt>
                            <dd>
                                <select class="post-editor-pill" name="discussion" aria-label="<?php echo t_attr('Discussion'); ?>">
                                    <?php $options(['open' => t('Open'), 'closed' => t('Closed')], $post->discussion); ?>
                                </select>
                            </dd>
                            <dt><i class="ph ph-user"></i><?php echo t('Author'); ?></dt>
                            <dd>
                                <?php if ($author !== '') : ?>
                                    <a class="post-editor-pill" href="<?php echo url('dashboard/profile?id=' . $post->authorId); ?>"><?php echo htmlspecialchars($author); ?></a>
                                <?php else : ?>
                                    <span class="post-editor-pill is-empty"><?php echo t('No author'); ?></span>
                                <?php endif; ?>
                            </dd>
                            <?php foreach ($facts as [$icon, $label, $fact]) : ?>
                                <dt><i class="<?php echo $icon; ?>"></i><?php echo $label; ?></dt>
                                <dd><span class="post-editor-pill<?php echo $fact === '' ? ' is-empty' : ''; ?>"><?php echo $fact !== '' ? htmlspecialchars($fact) : '—'; ?></span></dd>
                            <?php endforeach; ?>
                        </dl>
                        <?php
                    },
                ],
                ...($post->id > 0 ? [
                    [
                        'name'     => 'manage',
                        'type'     => 'custom',
                        'callback' => function () use ($post) {
                            $item    = htmlspecialchars(json_encode(['post-type' => $post->type, 'id' => $post->id]));
                            $copy    = htmlspecialchars(json_encode(['post-type' => $post->type, 'id' => $post->id, 'open' => 1]));
                            $confirm = htmlspecialchars(json_encode(t('Delete this item permanently?')));
                            ?>
                            <div class="post-editor-actions">
                                <?php if ($post->isTrashed()) : ?>
                                    <button class="btn btn--sm btn--outline" type="button" @click="$ajax.post('post/restore', <?php echo $item; ?>)">
                                        <i class="ph ph-arrow-counter-clockwise"></i> <?php echo t('Restore'); ?>
                                    </button>
                                    <button class="btn btn--sm btn--outline t-red" type="button" @click="confirm(<?php echo $confirm; ?>) && $ajax.post('post/delete', <?php echo $item; ?>)">
                                        <i class="ph ph-trash"></i> <?php echo t('Delete Permanently'); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="btn btn--sm btn--outline" type="button" @click="$ajax.post('post/duplicate', <?php echo $copy; ?>)">
                                        <i class="ph ph-copy"></i> <?php echo t('Duplicate'); ?>
                                    </button>
                                    <?php if ($post->canTrash()) : ?>
                                        <button class="btn btn--sm btn--outline t-red" type="button" @click="$ajax.post('post/delete', <?php echo $item; ?>)">
                                            <i class="ph ph-trash"></i> <?php echo t('Move to Trash'); ?>
                                        </button>
                                    <?php else : ?>
                                        <button class="btn btn--sm btn--outline t-red" type="button" @click="confirm(<?php echo $confirm; ?>) && $ajax.post('post/delete', <?php echo $item; ?>)">
                                            <i class="ph ph-trash"></i> <?php echo t('Delete'); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php
                        },
                    ],
                ] : []),
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
