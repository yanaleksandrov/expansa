<?php

use App\Models\Comment;
use App\Models\User;

/**
 * Comment of the post editor: the avatar with the initials, the author, the date, the status
 * when it is not approved, the quick moderation actions, the text and its replies below; shared by the Comments tab and the API
 * that adds a comment. Reply opens a field under the comment that sends to `comments/add`, closing the
 * other fields of a reply and the new comment one until it is cancelled or sent.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/post-comment.blade.php
 *
 * @var Comment                  $comment
 * @var array<int, Comment[]>    $replies Replies by the parent ID, see Comment::groupByParent().
 * @var bool                     $isOpen  Whether the discussion of the post is open, no Reply when it is closed.
 * @var int                      $depth   Level of the comment in its thread, no Reply at Comment::maxDepth().
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$replies  ??= [];
$isOpen   ??= true;
$depth    ??= 1;
$color    = $comment->getColor();
$date     = App\Support\Dates::format($comment->createdAt);
$statuses = Comment::statuses();
$canEdit  = User::current()?->can('manage_comments') ?? false;
$payload  = json_encode([
    'post-type' => $comment->postType,
    'id'        => $comment->postId,
    'parent_id' => $comment->id,
]);
?>
<div class="post-comment" id="post-comment-{{ $comment->id }}" u-data="{replying: false}" @comment-reply.window="replying = $event.detail === {{ $comment->id }}; replying && setTimeout(() => $refs.reply.focus())">
    <span class="avatar avatar--rounded avatar--sm bg-{{ $color }}-lt t-{{ $color }}">{{ $comment->getInitials() }}</span>
    <div class="post-comment-body">
        <div class="post-comment-who">
            <b>{{ $comment->getAuthorName() }}</b>
            @if ($date)
                <time datetime="{{ $comment->createdAt->format('c') }}">{{ $date }}</time>
            @endif
            @if ($comment->status !== Comment::STATUS_APPROVED)
                <span class="badge">{{ $statuses[$comment->status] ?? $comment->status }}</span>
            @endif
            @if ($canEdit)
                <span class="post-comment-tools ml-auto">
                    @foreach (Comment::moderationActions($comment->status) as $status => $action)
                        <button class="btn btn--xs btn--icon t-muted" type="button" title="{{ $action['label'] }}" @click="$ajax.post('comments/status', {id: {{ $comment->id }}, status: '{{ $status }}', stay: 1})">
                            <i class="{{ $action['icon'] }}"></i>
                        </button>
                    @endforeach
                    <a class="btn btn--xs btn--icon t-muted" href="{{ url('dashboard/comment?id=' . $comment->id . '&from=post') }}" title="{{ t('Edit Comment') }}" target="_blank">
                        <i class="ph ph-pencil-simple"></i>
                    </a>
                    <button class="btn btn--xs btn--icon t-muted" type="button" title="{{ t('Delete Comment') }}" @click="confirm(texts.deleteComment) && $ajax.post('comments/delete', {id: {{ $comment->id }}, stay: 1})">
                        <i class="ph ph-trash"></i>
                    </button>
                </span>
            @endif
        </div>
        <div class="post-comment-text">{{ $comment->content }}</div>
        @if ($canEdit && $isOpen && $depth < Comment::maxDepth())
            <div class="post-comment-actions">
                <button class="post-editor-pill" type="button" @click="$dispatch('comment-reply', replying ? 0 : {{ $comment->id }})">
                    <i class="ph ph-arrow-bend-up-left"></i> {{ t('Reply', context: 'action') }}
                </button>
            </div>
            <div class="post-comment-box" u-show="replying" hidden>
                <textarea id="post-comment-reply-{{ $comment->id }}" u-ref="reply" rows="2" placeholder="{{ t('Reply to :name…', $comment->getAuthorName()) }}" aria-label="{{ t('Reply') }}" @keydown="($event.ctrlKey || $event.metaKey) && $event.key === 'Enter' && $refs.send.click()" @keydown.esc="$dispatch('comment-reply', 0)"></textarea>
                <div class="post-comment-tools">
                    <button class="btn btn--sm" type="button" @click="$dispatch('comment-reply', 0)">{{ t('Cancel') }}</button>
                    <button class="btn btn--sm btn--primary" type="button" u-ref="send" @click="$refs.reply.value.trim() && $ajax.post('comments/add', {...{{ $payload }}, content: $refs.reply.value}).then(() => $dispatch('comment-reply', 0))">{{ t('Reply', context: 'action') }}</button>
                </div>
            </div>
        @endif
        <div class="post-comment-replies" id="post-comment-replies-{{ $comment->id }}">
            @foreach ($replies[$comment->id] ?? [] as $reply)
                <?php echo view('parts/post-comment', ['comment' => $reply, 'replies' => $replies, 'isOpen' => $isOpen, 'depth' => $depth + 1]); ?>
            @endforeach
        </div>
    </div>
</div>
