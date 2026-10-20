<?php

use App\Dashboard\Pages\Post as Page;
use App\Models\Comment;
use App\Models\User;
use App\Post\Status;
use App\Post\Type;
use App\Support\Activity;
use App\Support\Dates;
use App\Support\Permalinks;
use Expansa\Builders\Form;

$post = Page::post((string) filter_input(INPUT_GET, 'type') ?: 'pages', filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
$type = Type::get($post->type);

// the parents and the authors to choose aside; when there are more than the select shows, it searches on the server
$parents      = $type->hierarchical ? Page::parents($post) : [];
$parentSearch = count($parents) - 1 > Page::OPTIONS_LIMIT;
$parents      = array_slice($parents, 0, Page::OPTIONS_LIMIT + 1, true);
$authors      = Page::authors();
$authorSearch = count($authors) > Page::OPTIONS_LIMIT;
$authors      = array_slice($authors, 0, Page::OPTIONS_LIMIT, true);
if (! isset($authors[$post->authorId])) {
    $name    = Page::author($post);
    $authors = ($name !== '' ? [$post->authorId => $name] : ['' => t('No author')]) + $authors;
}

// the expression of `u-select`: the search with the payload of the route, none for a short list
$select = static fn (bool $search, string $route, array $payload = []): string => $search
    ? sprintf("{showSearch: true, events: {search: (text) => \$ajax.post('%s', {...%s, s: text}, null, {clearErrors: false})}}", $route, json_encode((object) $payload))
    : '{}';

// the URL of the post and of each parent, the base of the slug by the permalink structure;
// the paths of the post and the parents are read together
$paths   = $post::paths($post->type, [$post->id, ...array_keys($parents)]);
$url     = $post->url($paths[$post->id] ?? '');
$around  = Permalinks::around($paths[$post->parentId] ?? '', $post->id, $post->type);
$bases   = [];
foreach ($parents as $id => $title) {
    $bases[$id] = Permalinks::around($paths[$id] ?? '', $post->id, $post->type)[0];
}

// the discussion and the activity tabs show the same comments
$comments = Comment::getByPost($post->type, $post->id);

// the selects and the datepicker aside are written by hand, not by Form::view(): their scripts are connected here
Form::assets('select', 'select');
Form::assets('input', 'datetime-local');

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
        'u-data'          => 'tab as tab',
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
            'callback' => function () use ($post, $type, $url) {
                $back  = url('dashboard/edit?table=' . $type->key);
                $title = $post->id === 0 ? $type->labelAdd : ($post->title !== '' ? $post->title : $type->labelEdit);
                ?>
                <div class="post-editor-head">
                    <a class="btn btn--icon btn--xs" href="<?php echo $back; ?>" title="<?php echo t_attr('Back to :items', $type->labelNamePlural); ?>">
                        <i class="ph ph-arrow-left"></i>
                    </a>
                    <?php
                    echo view('parts/breadcrumbs', [
                        'items' => [
                            [
                                'label' => $type->labelNamePlural,
                                'url'   => $back,
                            ],
                            [
                                'label'      => $title,
                                'url'        => $url,
                                'attributes' => $url !== '' ? [
                                    'target'             => '_blank',
                                    'title'              => $type->labelView,
                                    '@slug-url.document' => '$el.href = $event.detail',
                                ] : [],
                            ],
                        ],
                    ]);
                    ?>
                    <?php if ($post->isTrashed()) : ?>
                        <span class="badge t-red"><?php echo t('In the trash'); ?></span>
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
            'icon'        => 'ph ph-article',
            'fields'      => [
	            [
		            'type'        => 'text',
		            'name'        => 'title',
		            'error'       => 'title',
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
			            'u-prop'      => 'title',
			            'value'       => $post->title,
			            'placeholder' => t('Add title...'),
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
				            'base'   => $around[0],
				            'suffix' => $around[1],
				            'parent' => 'parent_id',
				            'bases'  => $bases,
				            'link'   => $url,
			            ],
			            'attributes'  => [
				            'value'       => $post->slug,
				            'placeholder' => t('slug'),
				            'aria-label'  => t('Slug'),
			            ],
		            ],
	            ] : []),
                [
                    'type'   => 'group',
                    'name'   => 'main',
                    'label'  => '',
                    'layout' => 'plain',
                    'class'  => 'post-editor-main',
                    'fields' => [
	                    [
		                    'type'        => 'textarea',
		                    'name'        => 'excerpt',
		                    'error'       => 'excerpt',
		                    'label'       => '',
		                    'class'       => 'field field--plain',
		                    'label_class' => '',
		                    'reset'       => 0,
		                    'before'      => '',
		                    'after'       => '',
		                    'instruction' => t('An excerpt is an optional summary of your text that can be used in theme templates.'),
		                    'tooltip'     => '',
		                    'copy'        => 0,
		                    'validator'   => '',
		                    'conditions'  => [],
		                    'attributes'  => [
			                    'u-prop'      => 'excerpt',
			                    'value'       => $post->content,
			                    'placeholder' => t('Page excerpt...'),
		                    ],
	                    ],
	                    [
		                    'type'        => 'textarea',
		                    'name'        => 'content',
		                    'error'       => 'content',
		                    'label'       => t('Content'),
		                    'class'       => '',
		                    'label_class' => '',
		                    'reset'       => 0,
		                    'before'      => '',
		                    'after'       => '',
		                    'instruction' => t('HTML is allowed: unsafe tags and attributes are removed on saving.'),
		                    'tooltip'     => '',
		                    'copy'        => 0,
		                    'validator'   => '',
		                    'conditions'  => [],
		                    'attributes'  => [
			                    'rows'        => '20',
			                    'u-prop'      => 'content',
			                    'value'       => htmlspecialchars($post->content),
			                    'placeholder' => t('Content of the page...'),
		                    ],
	                    ],
                    ],
                ],
            ],
        ],
        [
            'type'        => 'tab',
            'label'       => t('Comments'),
            'name'        => 'comments',
            'caption'     => '',
            'description' => '',
            'icon'        => 'ph ph-chat-circle',
            'fields'      => [
                [
                    'name'     => 'comments',
                    'type'     => 'custom',
                    'callback' => function () use ($post, $comments) {
                        $user      = User::current();
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
                                            <div class="post-comment-submit">
                                                <span class="t-muted fs-12"><?php echo t('Ctrl+Enter to send'); ?></span>
                                                <button class="btn btn--sm btn--primary" type="submit" form="post-comment"><?php echo t('Comment', context: 'action'); ?></button>
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
		    'type'        => 'tab',
		    'label'       => t('Custom Code'),
		    'name'        => 'code',
		    'caption'     => '',
		    'description' => '',
		    'icon'        => 'ph ph-brackets-curly',
		    'fields'      => [
				[
					'type'   => 'group',
					'name'   => 'code',
					'label'  => '',
					'layout' => 'plain',
					'class'  => 'post-editor-main',
					'fields' => [
						[
							'type'        => 'textarea',
							'name'        => 'css',
							'error'       => 'css',
							'label'       => t('CSS code'),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t('CSS code'),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop'      => 'css',
								'placeholder' => t('CSS code'),
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
                    'callback' => function () use ($post, $comments) {
                        $lines = [];
                        foreach ($comments as $comment) {
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
            'type'   => 'group',
            'name'   => 'side',
            'label'  => '',
            'layout' => 'plain',
            'class'  => 'post-editor-side',
            'fields' => [
                [
                    'name'     => 'properties',
                    'type'     => 'custom',
                    'callback' => function () use ($post, $type, $parents, $parentSearch, $authors, $authorSearch, $select) {
                        $statuses = Status::getStatuses();
                        $options  = function (array $options, int|string $value): void {
                            foreach ($options as $key => $label) {
                                printf('<option value="%s"%s>%s</option>', htmlspecialchars((string) $key), (string) $key === (string) $value ? ' selected' : '', htmlspecialchars($label));
                            }
                        };
                        $facts    = $post->id === 0 ? [] : [
                            ['ph ph-clock-clockwise', t('Updated'), Dates::format($post->updatedAt)],
                            ['ph ph-eye', t('Views'), (string) $post->views],
                            ['ph ph-hash', t('ID'), (string) $post->id],
                        ];
                        ?>
                        <h4 class="post-editor-eyebrow"><?php echo t('Details'); ?></h4>
                        <dl class="post-editor-props" u-data="postDetails" data-status="<?php echo htmlspecialchars($post->status); ?>">
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
                                    <select class="field field--xs" name="parent_id" aria-label="<?php echo t_attr('Parent'); ?>" u-select="<?php echo htmlspecialchars($select($parentSearch, 'post/parents', ['post-type' => $post->type, 'id' => $post->id])); ?>">
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
                                <select class="field field--xs" name="author_id" aria-label="<?php echo t_attr('Author'); ?>" u-select="<?php echo htmlspecialchars($select($authorSearch, 'post/authors')); ?>">
                                    <?php $options($authors, $post->authorId ?: ''); ?>
                                </select>
                            </dd>
                            <?php if ($post->id > 0) : ?>
                                <dt><i class="ph ph-chat-circle-text"></i><?php echo t('Comments'); ?></dt>
                                <dd id="post-comments-count">
	                                <?php echo view('parts/post-comments-count', ['counts' => Comment::countByStatus($post->type, $post->id)]); ?>
                                </dd>
                            <?php endif; ?>
                            <dt><i class="ph ph-calendar-blank"></i><?php echo t('Created'); ?></dt>
                            <dd>
                                <input class="post-editor-pill" type="text" name="created_at" value="<?php echo htmlspecialchars(str_replace(' ', 'T', substr($post->createdAt, 0, 16))); ?>" placeholder="<?php echo t_attr('Now'); ?>" u-pickadate="{timepicker: true}" autocomplete="off" aria-label="<?php echo t_attr('Created'); ?>">
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
