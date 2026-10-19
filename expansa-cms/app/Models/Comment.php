<?php

declare(strict_types=1);

namespace App\Models;

use App\Post\Type;
use App\Support\Activity;
use DateTime;
use Expansa\Database\Model;
use Expansa\Database\Query;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Database\Traits\HasSoftDeletes;
use Expansa\Database\Traits\HasTimestamps;
use Expansa\Database\Traits\HasValidation;
use Expansa\Facades\Db;
use Expansa\Facades\I18n;
use Expansa\Facades\Log;
use Expansa\Facades\Mail;
use Expansa\Facades\View;
use Expansa\Support\Error;
use PDO;

/**
 * Represents a user comment on a post, including nested replies, author details,
 * status tracking, and reaction metrics. Creating, changing and deleting a comment
 * keeps the `comments` count of its post: the approved comments that are not deleted.
 *
 * @property int         $id          Unique identifier for the comment.
 * @property int         $postId      ID of the post this comment belongs to.
 * @property string      $postType    The type of the post this comment belongs to.
 * @property int         $parentId    ID of the parent comment (0 if top-level).
 * @property int         $authorId    ID of the registered author (0 if guest).
 * @property string      $authorName  Display name of the comment's author.
 * @property string      $authorEmail Email address of the comment's author.
 * @property string      $authorIp    IP address of the author.
 * @property string      $authorAgent User-Agent string of the author's browser/device.
 * @property string|null $locale      Language/locale code (e.g., "en", "de", "uk").
 * @property string      $status      Comment status (see Comment::STATUS_* constants).
 * @property string      $content     Main body text of the comment.
 * @property int         $likes       Number of likes.
 * @property int         $dislikes    Number of dislikes.
 * @property int|null    $rating      Optional numeric rating (e.g., for reviews).
 * @property DateTime    $createdAt   Creation timestamp.
 * @property DateTime    $updatedAt   Last update timestamp.
 * @property string|null $deletedAt   Item deleted timestamp.
 *
 * @package App\Models
 */
class Comment extends Model
{
    use HasSanitizing;
    use HasValidation;
    use HasTimestamps;
    use HasSoftDeletes;

    /**
     * The "pending" value of the `status` column.
     */
    public const string STATUS_PENDING = 'pending';

    /**
     * The "approved" value of the `status` column.
     */
    public const string STATUS_APPROVED = 'approved';

    /**
     * The "spam" value of the `status` column.
     */
    public const string STATUS_SPAM = 'spam';

    /**
     * The "rejected" value of the `status` column.
     */
    public const string STATUS_REJECTED = 'rejected';

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    public protected(set) string $table = 'comments';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<int, string>
     */
    public protected(set) array $fillable = [
        'post_id',
        'post_type',
        'parent_id',
        'author_id',
        'author_name',
        'author_email',
        'author_ip',
        'author_agent',
        'locale',
        'status',
        'content',
        'likes',
        'dislikes',
        'rating',
    ];

    /**
     * Array of rules for sanitize properties.
     *
     * @return array<string, string>
     */
    protected function getSanitizerRules(): array
    {
        return [
            'post_type'    => 'trim',
            'author_name'  => 'trim',
            'author_email' => 'email',
            'author_ip'    => 'trim',
            'author_agent' => 'trim',
            'locale'       => 'locale',
            'status'       => 'trim',
            'content'      => 'textarea',
        ];
    }

    /**
     * An array of rules for validation when creating and updating a model.
     *
     * @return array<string, string>
     */
    protected function validatorRules(): array
    {
        return [
            'content' => 'required',
            'status'  => 'in:' . implode(',', [
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
                self::STATUS_SPAM,
                self::STATUS_REJECTED,
            ]),
        ];
    }

    /**
     * Extend with custom validation rules.
     *
     * @return void
     */
    protected function validatorExtend(): void
    {
        $this->validator->extend(
            'status:in',
            t(
                'Status must be one of :pending, :approved, :spam or :rejected.',
                self::STATUS_PENDING,
                self::STATUS_APPROVED,
                self::STATUS_SPAM,
                self::STATUS_REJECTED
            )
        );
    }

    /**
     * Get the statuses with their human names, in the order of moderation.
     *
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING  => t('Pending'),
            self::STATUS_APPROVED => t('Approved'),
            self::STATUS_SPAM     => t('Spam'),
            self::STATUS_REJECTED => t('Rejected'),
        ];
    }

    /**
     * Get the quick moderation actions of a status: an approved comment is rejected or marked as spam,
     * any other one is approved.
     *
     * @param string $status Current status.
     * @return array<string, array{label: string, icon: string}> Actions by the status they set.
     */
    public static function moderationActions(string $status): array
    {
        if ($status !== self::STATUS_APPROVED) {
            return [self::STATUS_APPROVED => ['label' => t('Approve'), 'icon' => 'ph ph-check']];
        }

        return [
            self::STATUS_REJECTED => ['label' => t('Reject'), 'icon' => 'ph ph-prohibit'],
            self::STATUS_SPAM     => ['label' => t('Spam', context: 'action'), 'icon' => 'ph ph-warning-octagon'],
        ];
    }

    /**
     * Retrieves a comment by a given field.
     *
     * @param int|string $value A value for $by field. A comment ID.
     * @param string     $by    The field to retrieve the comment with. Defaults to 'id'.
     * @return Comment|Error
     */
    public static function find(int|string $value, string $by = 'id'): Comment|Error
    {
        if (empty($value)) {
            return error('comment-find', t('You are trying to find a comment with an empty :getByField.', $by));
        }

        $comment = parent::get($value, $by);

        return $comment instanceof Comment ? $comment : error('comment-find', t('Comment not found'));
    }

    /**
     * Create a new comment in the database.
     *
     * @param array $data
     * @return Comment|Error
     */
    public static function create(array $data): Comment|Error
    {
        $data += ['status' => self::STATUS_PENDING];

        $comment = new self($data);

        if (! $comment->isValid()) {
            return error('comment-add', $comment->getValidatorErrors());
        }

        if (! $comment->save() instanceof self) {
            return error('comment-add', t('Failed to save the comment to the database.'));
        }

        self::recount($comment->postType, $comment->postId);

        // the database fills the dates, the saved model does not have them yet
        $saved = self::find($comment->id);

        return $saved instanceof self ? $saved : $comment;
    }

    /**
     * Create an approved comment by a user of the dashboard: the user is the author, the request
     * gives the IP address and the browser.
     *
     * @param User  $user
     * @param array $data `post_id`, `post_type`, `content`, optional `parent_id` and `locale`.
     * @return Comment|Error
     */
    public static function createBy(User $user, array $data): Comment|Error
    {
        $comment = self::create([
            ...$data,
            'author_id'    => $user->id,
            'author_name'  => $user->showname ?: $user->login,
            'author_email' => $user->email ?? '',
            'author_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'author_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'locale'       => $data['locale'] ?? $user->locale,
            'status'       => self::STATUS_APPROVED,
        ]);

        if ($comment instanceof self) {
            $comment->notify();
        }

        return $comment;
    }

    /**
     * Submit a comment of a visitor from the site by the Discussions settings: signing in, the name
     * and the email, the depth of a reply; the comment is approved or held for moderation, then
     * the post author or the site owner gets an email.
     *
     * @param array     $data `post_id`, `post_type`, `content`, optional `parent_id`, `author_name`, `author_email`.
     * @param User|null $user Signed-in author, null for a guest.
     * @return Comment|Error
     */
    public static function submit(array $data, ?User $user): Comment|Error
    {
        $post = Post::get((string) ($data['post_type'] ?? ''), (int) ($data['post_id'] ?? 0));
        if (! $post instanceof Post || $post->status !== 'publish' || ! (Type::get($post->type)?->public ?? false)) {
            return error('comment-submit', t('Item not found'));
        }

        if (! $post->isDiscussionOpen()) {
            return error('comment-submit', t('The discussion is closed.'));
        }

        if ($user === null && Option::get('comments.registration', false)) {
            return error('comment-submit', t('Sign in to leave a comment.'));
        }

        $parentId = (int) ($data['parent_id'] ?? 0);
        if ($parentId > 0) {
            $parent = self::find($parentId);
            if (! $parent instanceof self || (int) $parent->postId !== $post->id || $parent->postType !== $post->type || $parent->status !== self::STATUS_APPROVED) {
                return error('comment-submit', t('Comment not found'));
            }

            if ($parent->depth() >= self::maxDepth()) {
                return error('comment-submit', t('This comment does not take replies.'));
            }
        }

        $name  = $user === null ? trim((string) ($data['author_name'] ?? '')) : ($user->showname ?: $user->login);
        $email = $user === null ? trim((string) ($data['author_email'] ?? '')) : (string) $user->email;
        if ($name === '') {
            return error('comment-submit', ['author_name' => t('Enter your name.')]);
        }

        if ($email === '' && $user === null && Option::get('comments.require_name_email', true)) {
            return error('comment-submit', ['author_email' => t('Enter your email.')]);
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return error('comment-submit', ['author_email' => t('Enter a valid email.')]);
        }

        $comment = self::create([
            'post_id'      => $post->id,
            'post_type'    => $post->type,
            'parent_id'    => $parentId,
            'author_id'    => $user->id ?? 0,
            'author_name'  => mb_substr($name, 0, 100),
            'author_email' => $email,
            'author_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'author_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'locale'       => $user->locale ?? I18n::locale(),
            'status'       => self::moderate($user, $email),
            'content'      => (string) ($data['content'] ?? ''),
        ]);

        if ($comment instanceof self) {
            $comment->notify();
        }

        return $comment;
    }

    /**
     * Get the status of a new comment of a visitor: approved for a moderator; pending when every comment
     * is approved manually, or the author must have an approved comment and has none, by the email or the account.
     *
     * @param User|null $user
     * @param string    $email
     * @return string
     */
    private static function moderate(?User $user, string $email): string
    {
        if ($user?->can('manage_comments')) {
            return self::STATUS_APPROVED;
        }

        if (Option::get('comments.moderation', true)) {
            return self::STATUS_PENDING;
        }

        if (! Option::get('comments.previously_approved', false)) {
            return self::STATUS_APPROVED;
        }

        $author   = $user === null ? ['author_email' => $email] : ['author_id' => $user->id];
        $approved = ($user !== null || $email !== '') && Db::has('comments', [
            ...$author,
            'status'     => self::STATUS_APPROVED,
            'deleted_at' => null,
        ]);

        return $approved ? self::STATUS_APPROVED : self::STATUS_PENDING;
    }

    /**
     * Email about the new comment by the Discussions settings: the owner of the site when it waits
     * for moderation, the author of the post when it is published by someone else.
     *
     * @return void
     */
    private function notify(): void
    {
        $post = $this->getPost();
        if (! $post instanceof Post) {
            return;
        }

        $pending = $this->status === self::STATUS_PENDING;
        if ($pending) {
            $email = Option::get('comments.notify_moderation', true) ? (string) User::owner()?->email : '';
        } else {
            $author = $post->authorId > 0 && $post->authorId !== (int) $this->authorId ? User::find($post->authorId) : null;
            $email  = $author instanceof User && Option::get('comments.notify_posts', true) ? (string) $author->email : '';
        }

        if ($email === '' || ! in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true)) {
            return;
        }

        $body = View::create('mails/wrapper', [
            'body_template' => 'mails/new-comment',
            'postTitle'     => $post->title,
            'author'        => $this->getAuthorName(),
            'content'       => $this->content,
            'link'          => url('dashboard/comment?id=' . $this->id),
            'pending'       => $pending,
        ])->render();

        $subject = $pending ? t('Comment awaiting moderation on «:title»', $post->title) : t('New comment on «:title»', $post->title);
        $message = Mail::to($email)->subject($subject)->message($body);
        if (! $message->send()) {
            Log::error('New comment: the email was not sent.', ['comment' => $this->id, 'error' => $message->error]);
        }
    }

    /**
     * Get the comments of a post that are not deleted, the oldest first.
     *
     * @param string $postType
     * @param int    $postId
     * @param int    $limit
     * @return Comment[]
     */
    public static function getByPost(string $postType, int $postId, int $limit = 200): array
    {
        if ($postId === 0) {
            return [];
        }

        return self::where([
            'post_id'   => $postId,
            'post_type' => $postType,
            'ORDER'     => ['id' => 'ASC'],
            'LIMIT'     => $limit,
        ])->find();
    }

    /**
     * Group comments into threads by the parent ID: `0` holds the top-level comments, and the replies
     * whose parent is not among the comments, e.g. deleted, so none of them is lost.
     *
     * @param Comment[] $comments
     * @return array<int, Comment[]>
     */
    public static function groupByParent(array $comments): array
    {
        $ids     = array_flip(array_map(fn (Comment $comment) => $comment->id, $comments));
        $threads = [];
        foreach ($comments as $comment) {
            $threads[isset($ids[$comment->parentId]) ? $comment->parentId : 0][] = $comment;
        }

        return $threads;
    }

    /**
     * Get the post the comment answers, null when its type or the post is gone.
     *
     * @return Post|null
     */
    public function getPost(): ?Post
    {
        return $this->postId > 0 ? Post::get((string) $this->postType, (int) $this->postId) : null;
    }

    /**
     * Get the public URL of the comment: the page of its post with the `#comment-{id}` anchor,
     * empty when the comment is not approved, the post is gone or its type is not public.
     *
     * @return string
     */
    public function getUrl(): string
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return '';
        }

        $url = $this->getPost()?->url() ?? '';

        return $url === '' ? '' : $url . '#comment-' . $this->id;
    }

    /**
     * Whether the comment takes replies: the discussion of its post is open and its thread is not
     * at the depth of the settings yet; a comment without a post takes none.
     *
     * @return bool
     */
    public function canReply(): bool
    {
        return $this->depth() < self::maxDepth() && ($this->getPost()?->isDiscussionOpen() ?? false);
    }

    /**
     * Get the deepest level of a thread from the Discussions settings: 1 when threaded comments are off,
     * so no comment takes replies, else the levels deep, from 2 to 10.
     *
     * @return int
     */
    public static function maxDepth(): int
    {
        if (! Option::get('comments.thread_comments', true)) {
            return 1;
        }

        return max(2, min(10, (int) Option::get('comments.thread_comments_depth', 5)));
    }

    /**
     * Get the level of the comment in its thread: 1 for a top-level one; the walk up the parents stops
     * at the deepest level of the settings, which is all that replying needs to know.
     *
     * @return int
     */
    public function depth(): int
    {
        $depth    = 1;
        $parentId = $this->parentId;
        $max      = self::maxDepth();
        while ($parentId > 0 && $depth < $max) {
            $parent = self::find($parentId);
            if (! $parent instanceof self) {
                break;
            }
            $parentId = $parent->parentId;
            $depth++;
        }

        return $depth;
    }

    /**
     * Get the name of the author, "Anonymous" for a comment without one.
     *
     * @return string
     */
    public function getAuthorName(): string
    {
        return $this->authorName !== '' ? $this->authorName : t('Anonymous');
    }

    /**
     * Get the initials of the author for the avatar: the first letters of up to two words of the name.
     *
     * @return string
     */
    public function getInitials(): string
    {
        return User::initials($this->getAuthorName());
    }

    /**
     * Get the color of the author avatar, the same for every comment of the name.
     *
     * @return string A name of the dashboard palette, e.g. `blue`.
     */
    public function getColor(): string
    {
        return User::color($this->getAuthorName());
    }

    /**
     * Update this comment in the database with the given attributes.
     *
     * @param array $data
     * @return Comment|Error
     */
    public function update(array $data): Comment|Error
    {
        [$postType, $postId] = [$this->postType, $this->postId];

        $before = $this->attributes;
        $this->fill($data);

        if (! $this->isValid()) {
            return error('comment-update', $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error('comment-update', t('Failed to save the comment to the database.'));
        }

        self::recount($this->postType, $this->postId);
        if ($postType !== $this->postType || $postId !== $this->postId) {
            self::recount($postType, $postId);
        }

        Activity::changes($this->table, (int) $this->id, $before, array_intersect_key($this->attributes, $data));

        return $this;
    }

    /**
     * Delete this comment: soft-deleted, see HasSoftDeletes.
     *
     * @return int The number of rows affected.
     */
    public function delete(): int
    {
        $count = new Query($this)->delete();

        self::recount($this->postType, $this->postId);
        if ($count > 0) {
            Activity::record($this->table, (int) $this->id, 'trashed');
        }

        return $count;
    }

    /**
     * Restore this comment after a soft deletion.
     *
     * @return int The number of rows affected.
     */
    public function restore(): int
    {
        $count = new Query($this)->restore();

        self::recount($this->postType, $this->postId);
        if ($count > 0) {
            Activity::record($this->table, (int) $this->id, 'restored');
        }

        return $count;
    }

    /**
     * Count the approved comments of a post that are not deleted, as kept in its `comments` column.
     *
     * @param string $postType
     * @param int    $postId
     * @return int
     */
    public static function countByPost(string $postType, int $postId): int
    {
        // the column is an unsigned SMALLINT
        return min((int) Db::count('comments', [
            'post_type'  => $postType,
            'post_id'    => $postId,
            'status'     => self::STATUS_APPROVED,
            'deleted_at' => null,
        ]), 65535);
    }

    /**
     * Count the comments of a post that are not deleted by status, e.g. for the post editor.
     *
     * @param string $postType
     * @param int    $postId
     * @return array<string, int> Counts by status, without the statuses that have no comments.
     */
    public static function countByStatus(string $postType, int $postId): array
    {
        $counts = Db::query(
            'SELECT status, COUNT(*) FROM <comments>
            WHERE post_type = :type AND post_id = :id AND deleted_at IS NULL
            GROUP BY status',
            [
                ':type' => $postType,
                ':id'   => $postId,
            ]
        )?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];

        return array_map(intval(...), $counts);
    }

    /**
     * Store the number of the approved comments that are not deleted in the `comments` column of the post.
     *
     * @param string $postType
     * @param int    $postId
     * @return void
     */
    public static function recount(string $postType, int $postId): void
    {
        $type = Type::get($postType);
        if ($type === null || $postId === 0) {
            return;
        }

        Db::update($type->table, ['comments' => self::countByPost($postType, $postId)], ['id' => $postId]);
    }
}
