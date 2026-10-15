<?php

use App\Dashboard\Pages\Post as Page;
use App\Models\Comment;
use App\Models\User;
use App\Post\Status;
use App\Post\Type;
use App\Support\Activity;
use Expansa\Builders\Form;
use Expansa\Facades\I18n;

$post = Page::post((string) filter_input(INPUT_GET, 'type') ?: 'pages', filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
$type = Type::get($post->type);
$date = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);

// the parents to choose in the details and the URL of each, the base of the slug
$parents = $type->hierarchical ? Page::parents($post) : [];
$paths   = $post::paths($post->type, array_keys($parents));
$bases   = [];
foreach ($parents as $id => $title) {
    $bases[$id] = rtrim(url($paths[$id] ?? ''), '/') . '/';
}

/**
 * Post editor: the bar with the way back and the public page over the tabs, the title, slug and content
 * in the main tab, the discussion in its own one, the publishing options and details aside.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'post-editor',
    [
        'class'           => 'tab post-editor',
        'u-data'          => 'tab',
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
            'name'     => 'header',
            'type'     => 'custom',
            'callback' => function () use ($post, $type) {
                $back  = url('dashboard/edit?table=' . $type->key);
                $path  = $post->path();
                $title = $post->id === 0 ? $type->labelAdd : ($post->title !== '' ? htmlspecialchars($post->title) : $type->labelEdit);
                ?>
                <div class="post-editor-head">
                    <a class="btn btn--icon btn--sm" href="<?php echo $back; ?>" title="<?php echo t_attr('Back to :items', $type->labelNamePlural); ?>">
                        <i class="ph ph-arrow-left"></i>
                    </a>
                    <nav class="post-editor-crumbs" aria-label="<?php echo t_attr('Breadcrumbs'); ?>">
                        <a href="<?php echo $back; ?>"><?php echo $type->labelNamePlural; ?></a>
                        <i class="ph ph-caret-right"></i>
                        <?php if ($path !== '') : ?>
                            <a href="<?php echo url($path); ?>" target="_blank" title="<?php echo htmlspecialchars($type->labelView); ?>" aria-current="page" @slug-url.document="$el.href = $event.detail"><?php echo $title; ?></a>
                        <?php else : ?>
                            <span aria-current="page"><?php echo $title; ?></span>
                        <?php endif; ?>
                    </nav>
                    <?php if ($post->isTrashed()) : ?>
                        <span class="badge t-red"><?php echo t('In the trash'); ?></span>
                    <?php endif; ?>
                </div>
                <?php
            },
        ],
        [
            'type'          => 'tab',
            'label'         => t('Main'),
            'name'          => 'main',
            'caption'       => '',
            'description'   => '',
            'icon'          => 'ph ph-article',
            'class_menu'    => '',
            'class_button'  => '',
            'class_content' => '',
            'fields'        => [
                [
                    'type'          => 'group',
                    'name'          => 'main',
                    'label'         => '',
                    'class'         => 'post-editor-main',
                    'label_class'   => '',
                    'content_class' => 'post-editor-card',
                    'fields'        => [
                        [
                            'type'        => 'text',
                            'name'        => 'title',
                            'error'       => 'title',
                            'label'       => '',
                            'class'       => 'field field--plain post-editor-heading',
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
                                'u-prop'      => 'title',
                                'value'       => $post->title,
                                'placeholder' => t('Title'),
                                'aria-label'  => t('Title'),
                                'autofocus'   => $post->id === 0,
                                'required'    => true,
                            ],
                        ],
                        ...($type->public ? [
                            [
                                'type'        => 'slug',
                                'name'        => 'slug',
                                'error'       => 'slug',
                                'label'       => '',
                                'class'       => 'field post-editor-slug',
                                'label_class' => '',
                                'instruction' => '',
                                'tooltip'     => t('The address of the page: letters, numbers and hyphens. Leave it empty to make it from the title.'),
                                'conditions'  => [],
                                'options'     => [
                                    'from'   => 'title',
                                    'base'   => $bases[$post->parentId] ?? rtrim(url(), '/') . '/',
                                    'parent' => 'parent_id',
                                    'bases'  => $bases,
                                    'link'   => $post->slug !== '' ? url($post->path()) : '',
                                ],
                                'attributes'  => [
                                    'value'       => $post->slug,
                                    'placeholder' => t('Slug'),
                                    'aria-label'  => t('Slug'),
                                ],
                            ],
                        ] : []),
                        [
                            'name'     => 'content',
                            'type'     => 'custom',
                            'callback' => function () use ($post) {
                                ?>
                                <section class="post-editor-section">
                                    <div class="post-editor-section-head">
                                        <h3 id="post-editor-content"><?php echo t('Content'); ?></h3>
                                        <span><?php echo t('HTML is allowed: unsafe tags and attributes are removed on saving.'); ?></span>
                                    </div>
                                    <label class="field-item">
                                        <textarea class="post-editor-text" name="content" u-prop="content" rows="16" aria-labelledby="post-editor-content"><?php echo htmlspecialchars($post->content); ?></textarea>
                                    </label>
                                </section>
                                <?php
                            },
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'          => 'tab',
            'label'         => t('Comments'),
            'name'          => 'comments',
            'caption'       => '',
            'description'   => '',
            'icon'          => 'ph ph-chat-circle',
            'class_menu'    => '',
            'class_button'  => '',
            'class_content' => '',
            'fields'        => [
                [
                    'name'     => 'comments',
                    'type'     => 'custom',
                    'callback' => function () use ($post) {
                        $comments   = Comment::getByPost($post->type, $post->id);
                        $user       = User::current();
                        $name       = $user === null ? '' : ($user->showname ?: $user->login);
                        $canComment = $user?->can('manage_comments') ?? false;
                        ?>
                        <div class="post-editor-main">
                            <div class="post-editor-card post-comments" u-data="{replying: 0}" @comment-reply.window="replying = $event.detail">
                                <div class="post-editor-section-head">
                                    <h3><?php echo t('Comments'); ?></h3>
                                </div>
                                <div class="post-comments-list">
                                    <?php $threads = Comment::groupByParent($comments); ?>
                                    <?php foreach ($threads[0] ?? [] as $comment) : ?>
                                        <?php echo view('parts/post-comment', ['comment' => $comment, 'replies' => $threads, 'isOpen' => $post->isDiscussionOpen()]); ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($post->id === 0) : ?>
                                    <p class="post-comments-empty"><?php echo t('Save the item to start the conversation.'); ?></p>
                                <?php else : ?>
                                    <p class="post-comments-empty"><?php echo $canComment ? t('No comments yet. Start the conversation.') : t('No comments yet.'); ?></p>
                                <?php endif; ?>
                                <?php if ($post->id > 0 && ! $post->isDiscussionOpen()) : ?>
                                    <p class="post-comments-closed">
                                        <i class="ph ph-lock-simple"></i> <?php echo t('The discussion is closed: new comments and replies are not accepted.'); ?>
                                    </p>
                                <?php elseif ($post->id > 0 && $canComment) : ?>
                                    <div class="post-comment" u-show="!replying">
                                        <span class="avatar avatar--rounded avatar--sm bg-blue-lt t-blue"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($name, 0, 1))); ?></span>
                                        <div class="post-comment-box">
                                            <textarea id="post-comment-text" name="content" form="post-comment" rows="2" required placeholder="<?php echo t_attr('Leave a comment…'); ?>" aria-label="<?php echo t_attr('Comment'); ?>" @keydown="($event.ctrlKey || $event.metaKey) && $event.key === 'Enter' && $el.form.requestSubmit()"></textarea>
                                            <div class="post-comment-tools">
                                                <span class="t-muted fs-12"><?php echo t('Ctrl+Enter to send'); ?></span>
                                                <button class="btn btn--sm btn--primary" type="submit" form="post-comment"><?php echo t('Comment'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    },
                ],
            ],
        ],
        [
            'type'          => 'tab',
            'label'         => t('Activity'),
            'name'          => 'activity',
            'caption'       => '',
            'description'   => '',
            'icon'          => 'ph ph-clock-counter-clockwise',
            'class_menu'    => '',
            'class_button'  => '',
            'class_content' => '',
            'fields'        => [
                [
                    'name'     => 'activity',
                    'type'     => 'custom',
                    'callback' => function () use ($post) {
                        $lines = [];
                        foreach (Comment::getByPost($post->type, $post->id) as $comment) {
                            if ($comment->deletedAt !== null) {
                                continue;
                            }
                            $lines[] = [
                                'icon' => 'ph ph-chat-circle-text',
                                'user' => $comment->getAuthorName(),
                                'text' => t('Left a comment'),
                                'note' => mb_strimwidth(trim(strip_tags($comment->content)), 0, 80, '…'),
                                'at'   => $comment->createdAt?->format('Y-m-d H:i:s') ?? '',
                            ];
                        }
                        if ($post->id > 0) {
                            $lines[] = [
                                'icon' => 'ph ph-plus',
                                'user' => Page::author($post),
                                'text' => t('Created'),
                                'note' => '',
                                'at'   => $post->createdAt,
                            ];
                        }
                        echo view('parts/activity', ['lines' => Activity::timeline($post->table, $post->id, $lines)]);
                    },
                ],
            ],
        ],
        [
            'type'          => 'group',
            'name'          => 'side',
            'label'         => '',
            'class'         => 'post-editor-side',
            'label_class'   => '',
            'content_class' => 'dg g-5',
            'fields'        => [
                [
                    'name'     => 'properties',
                    'type'     => 'custom',
                    'callback' => function () use ($post, $type, $date, $parents) {
                        $author   = Page::author($post);
                        $statuses = Status::getStatuses();
                        $options  = function (array $options, int|string $value): void {
                            foreach ($options as $key => $label) {
                                printf('<option value="%s"%s>%s</option>', htmlspecialchars((string) $key), (string) $key === (string) $value ? ' selected' : '', htmlspecialchars($label));
                            }
                        };
                        $facts    = $post->id === 0 ? [] : [
                            ['ph ph-calendar-blank', t('Created'), $post->createdAt ? $date->format(strtotime($post->createdAt)) : ''],
                            ['ph ph-clock-clockwise', t('Updated'), $post->updatedAt ? $date->format(strtotime($post->updatedAt)) : ''],
                            ['ph ph-eye', t('Views'), (string) $post->views],
                            ['ph ph-hash', t('ID'), (string) $post->id],
                        ];
                        ?>
                        <h4 class="post-editor-eyebrow"><?php echo t('Details'); ?></h4>
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
                                <?php if ($author) : ?>
                                    <a class="post-editor-pill" href="<?php echo url('dashboard/profile?id=' . $post->authorId); ?>"><?php echo htmlspecialchars($author); ?></a>
                                <?php else : ?>
                                    <span class="post-editor-pill is-empty"><?php echo t('No author'); ?></span>
                                <?php endif; ?>
                            </dd>
                            <?php if ($post->id > 0) : ?>
                                <dt><i class="ph ph-chat-circle-text"></i><?php echo t('Comments'); ?></dt>
                                <dd id="post-comments-count"><?php echo view('parts/post-comments-count', ['counts' => Comment::countByStatus($post->type, $post->id)]); ?></dd>
                            <?php endif; ?>
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
                            ?>
                            <div class="post-editor-actions">
                                <?php if ($post->isTrashed()) : ?>
                                    <button class="btn btn--sm btn--outline" type="button" @click="$ajax.post('post/restore', <?php echo $item; ?>)">
                                        <i class="ph ph-arrow-counter-clockwise"></i> <?php echo t('Restore'); ?>
                                    </button>
                                    <button class="btn btn--sm btn--outline t-red" type="button" @click="confirm(texts.deleteItem) && $ajax.post('post/delete', <?php echo $item; ?>)">
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
                                        <button class="btn btn--sm btn--outline t-red" type="button" @click="confirm(texts.deleteItem) && $ajax.post('post/delete', <?php echo $item; ?>)">
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
