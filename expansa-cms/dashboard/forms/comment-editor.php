<?php

use App\Dashboard\Pages\Comment as Page;
use App\Models\Comment;
use Expansa\Builders\Form;
use Expansa\Facades\I18n;

$comment = Page::comment(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);

/**
 * Comment page: the text, status and author of a comment, the facts of where it came from.
 *
 * @since 2027.1
 */
return Form::enqueue(
    'comment-editor',
    [
        'class'           => 'tab',
        'u-data'          => 'tab',
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
            'type'          => 'tab',
            'label'         => t('Comment'),
            'name'          => 'comment',
            'caption'       => '',
            'description'   => '',
            'icon'          => 'ph ph-chat-text',
            'class_menu'    => '',
            'class_button'  => '',
            'class_content' => 'p-7 sm:p-5',
            'fields'        => [
                [
                    'type'          => 'group',
                    'name'          => 'text',
                    'label'         => t('Comment'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
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
                                'u-prop'   => 'content',
                                'rows'     => max(3, count(explode("\n", $comment->content))),
                                'value'    => $comment->content,
                                'required' => true,
                            ],
                        ],
                        [
                            'type'        => 'select',
                            'name'        => 'status',
                            'error'       => 'status',
                            'label'       => t('Status'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Only approved comments are shown on the site.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'status',
                                'value'  => $comment->status,
                            ],
                            'options'     => Comment::statuses(),
                        ],
                        [
                            'type'        => 'number',
                            'name'        => 'rating',
                            'error'       => 'rating',
                            'label'       => t('Rating'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('For reviews; leave it empty for a usual comment.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'rating',
                                'min'    => 0,
                                'value'  => $comment->rating ?? '',
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'author',
                    'label'         => t('Author'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-6 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'text',
                            'name'        => 'author_name',
                            'error'       => 'author_name',
                            'label'       => t('Name'),
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
        [
            'type'          => 'tab',
            'label'         => t('Reply'),
            'name'          => 'reply',
            'caption'       => '',
            'description'   => '',
            'icon'          => 'ph ph-arrow-bend-up-left',
            'class_menu'    => '',
            'class_button'  => '',
            'class_content' => 'p-7 sm:p-5',
            'fields'        => [
                [
                    'type'          => 'group',
                    'name'          => 'reply',
                    'label'         => t('Reply'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'reply',
                            'type'     => 'custom',
                            'callback' => function () {
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('The reply is published at once under your name, on the same post.'); ?></div>
                                    <div class="field">
                                        <div class="field-item">
                                            <textarea id="comment-reply-text" name="reply" form="comment-reply" rows="4" required placeholder="<?php echo t_attr('Write a reply'); ?>"></textarea>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn--primary" type="submit" form="comment-reply">
                                            <i class="ph ph-paper-plane-tilt"></i> <?php echo t('Send Reply'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'          => 'tab',
            'label'         => t('Details'),
            'name'          => 'details',
            'caption'       => '',
            'description'   => '',
            'icon'          => 'ph ph-info',
            'class_menu'    => '',
            'class_button'  => '',
            'class_content' => 'p-7 sm:p-5',
            'fields'        => [
                [
                    'type'          => 'group',
                    'name'          => 'origin',
                    'label'         => t('Details'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'origin',
                            'type'     => 'custom',
                            'callback' => function () use ($comment) {
                                $date  = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
                                $title = Page::postTitle($comment);
                                $facts = [
                                    t('In Response To') => $title !== '' ? $title : t('Post #:id', $comment->postId),
                                    t('Reply To')       => $comment->parentId > 0 ? t('Comment #:id', $comment->parentId) : '—',
                                    t('Submitted')      => $comment->createdAt ? $date->format($comment->createdAt) : '—',
                                    t('Updated')        => $comment->updatedAt ? $date->format($comment->updatedAt) : '—',
                                    t('Likes')          => $comment->likes . ' / ' . $comment->dislikes,
                                    t('IP Address')     => $comment->authorIp ?: '—',
                                    t('Browser')        => $comment->authorAgent ?: '—',
                                ];
                                ?>
                                <dl class="dg g-2 ga-4 fs-13">
                                    <?php foreach ($facts as $label => $fact) : ?>
                                        <div class="df g-2">
                                            <dt class="t-muted"><?php echo $label; ?>:</dt>
                                            <dd><?php echo htmlspecialchars((string) $fact); ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                                <?php
                            },
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'removal',
                    'label'         => t('Delete Comment'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'removal',
                            'type'     => 'custom',
                            'callback' => function () use ($comment) {
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('The comment disappears from the site and the list. Its replies stay.'); ?></div>
                                    <div>
                                        <button class="btn btn--outline t-red" type="button" @click="confirm('<?php echo t_attr('Delete this comment?'); ?>') && $ajax.post('comments/delete', {id: <?php echo $comment->id; ?>})">
                                            <i class="ph ph-trash"></i> <?php echo t('Delete'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php
                            },
                        ],
                    ],
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
