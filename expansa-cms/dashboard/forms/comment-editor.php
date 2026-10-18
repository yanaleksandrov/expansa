<?php

use App\Dashboard\Pages\Comment as Page;
use App\Models\Comment;
use App\Post\Type;
use App\Support\Activity;
use Expansa\Builders\Form;
use Expansa\Facades\I18n;

$comment = Page::comment(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
$from    = filter_input(INPUT_GET, 'from') === 'post' ? 'post' : '';
$type    = Type::get($comment->postType);
$date    = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
$postUrl = $type !== null && $comment->postId > 0 ? url('dashboard/post?type=' . $type->key . '&id=' . $comment->postId . '&tab=comments') : '';

/**
 * Comment page, laid out as the post editor: the bar with the way back over the tabs, the text and the author
 * in the main card, a reply in its tab, the status, the rating and the facts of where the comment came from aside.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'comment-editor',
    [
        'class'           => 'tab post-editor',
        'u-data'          => 'tab as tab',
        '@load'           => '$dirty.watch($el)',
        '@submit.prevent' => '$ajax.post("comments/update", "", () => $dirty.remove($el))',
    ],
    [
        [
            'type'       => 'hidden',
            'name'       => 'id',
            'attributes' => ['type' => 'hidden', 'name' => 'id', 'value' => $comment->id],
        ],
        [
            'name'     => 'header',
            'type'     => 'custom',
            'callback' => function () use ($comment, $from, $postUrl, $type) {
                $list  = url('dashboard/edit?table=comments');
                $title = Page::postTitle($comment);
                $back  = $from === 'post' && $postUrl !== '' ? $postUrl : $list;
                $url   = $comment->getUrl();
                ?>
                <div class="post-editor-head">
                    <a class="btn btn--icon btn--xs" href="<?php echo $back; ?>" title="<?php echo t_attr('Back'); ?>">
                        <i class="ph ph-arrow-left"></i>
                    </a>
                    <nav class="post-editor-crumbs" aria-label="<?php echo t_attr('Breadcrumbs'); ?>">
                        <?php if ($from === 'post' && $postUrl !== '') : ?>
                            <a href="<?php echo url('dashboard/edit?table=' . $type->key); ?>"><?php echo $type->labelNamePlural; ?></a>
                            <i class="ph ph-caret-right"></i>
                            <a href="<?php echo $postUrl; ?>"><?php echo htmlspecialchars($title !== '' ? $title : t('Post #:id', $comment->postId)); ?></a>
                        <?php else : ?>
                            <a href="<?php echo $list; ?>"><?php echo t('Comments'); ?></a>
                        <?php endif; ?>
                        <i class="ph ph-caret-right"></i>
                        <?php if ($url !== '') : ?>
                            <a href="<?php echo $url; ?>" target="_blank" title="<?php echo t_attr('View comment'); ?>" aria-current="page"><?php echo t('Comment #:id', $comment->id); ?></a>
                        <?php else : ?>
                            <span aria-current="page"><?php echo t('Comment #:id', $comment->id); ?></span>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php
            },
        ],
        [
            'type'        => 'tab',
            'label'       => t('Comment'),
            'name'        => 'comment',
            'caption'     => '',
            'description' => '',
            'icon'        => 'ph ph-chat-text',
            'fields'      => [
                [
                    'type'   => 'group',
                    'name'   => 'main',
                    'label'  => '',
                    'layout' => 'plain',
                    'class'  => 'post-editor-main',
                    'fields' => [
                        [
                            'type'        => 'textarea',
                            'name'        => 'content',
                            'error'       => 'content',
                            'label'       => t('Text'),
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
                                'u-prop'      => 'content',
                                'placeholder' => t('Text of the comment'),
                                'value'       => $comment->content,
                                'required'    => true,
                            ],
                        ],
                        [
                            'type'    => 'group',
                            'name'    => 'author',
                            'label'   => '',
                            'layout'  => 'plain',
                            'columns' => 2,
                            'fields'  => [
                                [
                                    'type'        => 'text',
                                    'name'        => 'author_name',
                                    'error'       => 'author_name',
                                    'label'       => t('Author'),
                                    'class'       => '',
                                    'label_class' => '',
                                    'reset'       => 0,
                                    'before'      => '<i class="ph ph-user"></i>',
                                    'after'       => '',
                                    'instruction' => '',
                                    'tooltip'     => '',
                                    'copy'        => 0,
                                    'validator'   => '',
                                    'conditions'  => [],
                                    'attributes'  => [
                                        'u-prop' => 'author_name',
                                        'value'  => $comment->authorName,
                                    ],
                                ],
                                [
                                    'type'        => 'email',
                                    'name'        => 'author_email',
                                    'error'       => 'author_email',
                                    'label'       => t('Email'),
                                    'class'       => '',
                                    'label_class' => '',
                                    'reset'       => 0,
                                    'before'      => '<i class="ph ph-at"></i>',
                                    'after'       => '',
                                    'instruction' => t('Not displayed publicly.'),
                                    'tooltip'     => '',
                                    'copy'        => 0,
                                    'validator'   => '',
                                    'conditions'  => [],
                                    'attributes'  => [
                                        'u-prop' => 'author_email',
                                        'value'  => $comment->authorEmail,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'        => 'tab',
            'label'       => t('Reply'),
            'name'        => 'reply',
            'caption'     => '',
            'description' => '',
            'icon'        => 'ph ph-arrow-bend-up-left',
            'fields'      => [
                [
                    'name'     => 'reply',
                    'type'     => 'custom',
                    'callback' => function () use ($comment) {
                        ?>
                        <div class="post-editor-main">
                            <div class="post-editor-card post-comments">
                                <div class="post-editor-section-head">
                                    <h3><?php echo t('Reply'); ?></h3>
                                </div>
                                <div class="post-comment">
                                    <span class="avatar avatar--rounded avatar--sm bg-<?php echo $comment->getColor(); ?>-lt t-<?php echo $comment->getColor(); ?>"><?php echo htmlspecialchars($comment->getInitials()); ?></span>
                                    <div class="post-comment-body">
                                        <div class="post-comment-who"><b><?php echo htmlspecialchars($comment->getAuthorName()); ?></b></div>
                                        <div class="post-comment-text"><?php echo htmlspecialchars($comment->content); ?></div>
                                    </div>
                                </div>
                                <?php if (! $comment->canReply()) : ?>
                                    <p class="post-comments-closed">
                                        <i class="ph ph-lock-simple"></i> <?php echo t('The discussion is closed: new comments and replies are not accepted.'); ?>
                                    </p>
                                <?php else : ?>
                                <div class="post-comment-box">
                                    <textarea id="comment-reply-text" name="reply" form="comment-reply" rows="3" required placeholder="<?php echo t_attr('Reply to :name…', $comment->getAuthorName()); ?>" aria-label="<?php echo t_attr('Reply'); ?>" @keydown="($event.ctrlKey || $event.metaKey) && $event.key === 'Enter' && $el.form.requestSubmit()"></textarea>
                                    <div class="post-comment-tools">
                                        <span class="t-muted fs-12 mr-auto"><?php echo t('The reply is published at once under your name, on the same post.'); ?></span>
                                        <button class="btn btn--sm btn--primary" type="submit" form="comment-reply"><?php echo t('Send Reply'); ?></button>
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
            'label'       => t('Activity'),
            'name'        => 'activity',
            'caption'     => '',
            'description' => '',
            'icon'        => 'ph ph-clock-counter-clockwise',
            'fields'      => [
                [
                    'name'     => 'activity',
                    'type'     => 'custom',
                    'callback' => function () use ($comment) {
                        $lines = [
                            [
                                'icon' => 'ph ph-paper-plane-tilt',
                                'user' => $comment->getAuthorName(),
                                'text' => t('Submitted'),
                                'note' => '',
                                'at'   => $comment->createdAt?->format('Y-m-d H:i:s') ?? '',
                            ],
                        ];
                        foreach (Comment::getByPost($comment->postType, (int) $comment->postId) as $reply) {
                            if ((int) $reply->parentId !== (int) $comment->id) {
                                continue;
                            }
                            $lines[] = [
                                'icon' => 'ph ph-arrow-bend-up-left',
                                'user' => $reply->getAuthorName(),
                                'text' => t('Replied'),
                                'note' => mb_strimwidth(trim(strip_tags($reply->content)), 0, 80, '…'),
                                'at'   => $reply->createdAt?->format('Y-m-d H:i:s') ?? '',
                            ];
                        }
                        echo view('parts/activity', ['lines' => Activity::timeline($comment->table, (int) $comment->id, $lines)]);
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
                    'callback' => function () use ($comment, $date, $postUrl, $from) {
                        $title   = Page::postTitle($comment);
                        $options = function (array $options, string $value): void {
                            foreach ($options as $key => $label) {
                                printf('<option value="%s"%s>%s</option>', htmlspecialchars((string) $key), (string) $key === $value ? ' selected' : '', htmlspecialchars($label));
                            }
                        };
                        $facts   = [
                            ['ph ph-calendar-plus', t('Submitted'), $comment->createdAt ? $date->format($comment->createdAt) : ''],
                            ['ph ph-clock-clockwise', t('Updated'), $comment->updatedAt ? $date->format($comment->updatedAt) : '', 'comment-updated'],
                            ['ph ph-thumbs-up', t('Likes'), $comment->likes . ' / ' . $comment->dislikes],
                            ['ph ph-globe-simple', t('IP Address'), (string) $comment->authorIp],
                            ['ph ph-browser', t('Browser'), (string) $comment->authorAgent],
                            ['ph ph-hash', t('ID'), (string) $comment->id],
                        ];
                        ?>
                        <h4 class="post-editor-eyebrow"><?php echo t('Details'); ?></h4>
                        <dl class="post-editor-props">
                            <dt><i class="ph ph-circle-dashed"></i><?php echo t('Status'); ?></dt>
                            <dd>
                                <select class="post-editor-pill" name="status" aria-label="<?php echo t_attr('Status'); ?>">
                                    <?php $options(Comment::statuses(), (string) $comment->status); ?>
                                </select>
                            </dd>
                            <dt><i class="ph ph-star"></i><?php echo t('Rating'); ?></dt>
                            <dd>
                                <input class="post-editor-pill" type="number" min="0" name="rating" value="<?php echo htmlspecialchars((string) ($comment->rating ?? '')); ?>" placeholder="<?php echo t_attr('No rating'); ?>" aria-label="<?php echo t_attr('Rating'); ?>">
                            </dd>
                            <dt><i class="ph ph-article"></i><?php echo t('In reply to'); ?></dt>
                            <dd>
                                <?php if ($postUrl !== '') : ?>
                                    <a class="post-editor-pill" href="<?php echo $postUrl; ?>"><?php echo htmlspecialchars($title !== '' ? $title : t('Post #:id', $comment->postId)); ?></a>
                                <?php else : ?>
                                    <span class="post-editor-pill is-empty"><?php echo t('Post #:id', $comment->postId); ?></span>
                                <?php endif; ?>
                            </dd>
                            <dt><i class="ph ph-arrow-bend-up-left"></i><?php echo t('Reply To'); ?></dt>
                            <dd>
                                <?php if ($comment->parentId > 0) : ?>
                                    <a class="post-editor-pill" href="<?php echo url('dashboard/comment?id=' . $comment->parentId . ($from !== '' ? '&from=' . $from : '')); ?>"><?php echo t('Comment #:id', $comment->parentId); ?></a>
                                <?php else : ?>
                                    <span class="post-editor-pill is-empty">—</span>
                                <?php endif; ?>
                            </dd>
                            <?php foreach ($facts as $row) : ?>
                                <?php [$icon, $label, $fact, $id] = $row + [3 => '']; ?>
                                <dt><i class="<?php echo $icon; ?>"></i><?php echo $label; ?></dt>
                                <dd<?php echo $id !== '' ? ' id="' . $id . '"' : ''; ?>><span class="post-editor-pill<?php echo $fact === '' ? ' is-empty' : ''; ?>" title="<?php echo htmlspecialchars($fact); ?>"><?php echo $fact !== '' ? htmlspecialchars($fact) : '—'; ?></span></dd>
                            <?php endforeach; ?>
                        </dl>
                        <?php
                    },
                ],
                [
                    'name'     => 'manage',
                    'type'     => 'custom',
                    'callback' => function () use ($comment, $from) {
                        ?>
                        <div class="post-editor-actions">
                            <button class="btn btn--sm btn--outline t-red" type="button" @click="confirm(texts.deleteComment) && $ajax.post('comments/delete', {id: <?php echo $comment->id; ?>, from: '<?php echo $from; ?>'})">
                                <i class="ph ph-trash"></i> <?php echo t('Delete'); ?>
                            </button>
                        </div>
                        <?php
                    },
                ],
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
