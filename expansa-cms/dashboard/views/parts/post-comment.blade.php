<?php

use App\Models\Comment;
use App\Models\User;
use Expansa\Facades\I18n;

/**
 * Comment of the post editor: the avatar with the initials, the author, the date, the status
 * when it is not approved, the text and its replies below; shared by the Comments tab and the API
 * that adds a comment. Reply opens a field under the comment that sends to `comments/add`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/post-comment.blade.php
 *
 * @var Comment                  $comment
 * @var array<int, Comment[]>    $replies Replies by the parent ID, see Comment::groupByParent().
 * @var bool                     $isOpen  Whether the discussion of the post is open, no Reply when it is closed.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$replies  ??= [];
$isOpen   ??= true;
$color    = $comment->getColor();
$date     = $comment->createdAt ? new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT)->format($comment->createdAt) : '';
$statuses = Comment::statuses();
$canEdit  = User::current()?->can('manage_comments') ?? false;
$payload  = json_encode([
    'post-type' => $comment->postType,
    'id'        => $comment->postId,
    'parent_id' => $comment->id,
]);
?>
<div class="post-comment" id="post-comment-{{ $comment->id }}" u-data="{replying: false}">
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
                    <a class="btn btn--xs btn--icon t-muted" href="{{ url('dashboard/comment?id=' . $comment->id . '&from=post') }}" title="{{ t('Edit Comment') }}">
                        <i class="ph ph-pencil-simple"></i>
                    </a>
                    <button class="btn btn--xs btn--icon t-muted" type="button" title="{{ t('Delete Comment') }}" @click="confirm({{ json_encode(t('Delete this comment? Its replies stay.')) }}) && $ajax.post('comments/delete', {id: {{ $comment->id }}, stay: 1})">
                        <i class="ph ph-trash"></i>
                    </button>
                </span>
            @endif
        </div>
        <div class="post-comment-text">{{ $comment->content }}</div>
        @if ($canEdit && $isOpen)
            <div class="post-comment-actions">
                <button class="post-editor-pill" type="button" @click="replying = !replying; replying && setTimeout(() => $refs.reply.focus())">
                    <i class="ph ph-arrow-bend-up-left"></i> {{ t('Reply') }}
                </button>
            </div>
            <div class="post-comment-box" u-show="replying" hidden>
                <textarea id="post-comment-reply-{{ $comment->id }}" u-ref="reply" rows="2" placeholder="{{ t('Reply to :name…', $comment->getAuthorName()) }}" aria-label="{{ t('Reply') }}" @keydown="($event.ctrlKey || $event.metaKey) && $event.key === 'Enter' && $refs.send.click()" @keydown.esc="replying = false"></textarea>
                <div class="post-comment-tools">
                    <button class="btn btn--sm" type="button" @click="replying = false">{{ t('Cancel') }}</button>
                    <button class="btn btn--sm btn--primary" type="button" u-ref="send" @click="$refs.reply.value.trim() && $ajax.post('comments/add', {...{{ $payload }}, content: $refs.reply.value}).then(() => replying = false)">{{ t('Reply') }}</button>
                </div>
            </div>
        @endif
        <div class="post-comment-replies" id="post-comment-replies-{{ $comment->id }}">
            @foreach ($replies[$comment->id] ?? [] as $reply)
                <?php echo view('parts/post-comment', ['comment' => $reply, 'replies' => $replies, 'isOpen' => $isOpen]); ?>
            @endforeach
        </div>
    </div>
</div>
