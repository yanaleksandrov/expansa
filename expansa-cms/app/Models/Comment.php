<?php

declare(strict_types=1);

namespace App\Models;

use App\Post\Type;
use DateTime;
use Expansa\Database\Model;
use Expansa\Database\Query;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Database\Traits\HasSoftDeletes;
use Expansa\Database\Traits\HasTimestamps;
use Expansa\Database\Traits\HasValidation;
use Expansa\Facades\Db;
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
        return self::create([
            ...$data,
            'author_id'    => $user->id,
            'author_name'  => $user->showname ?: $user->login,
            'author_email' => $user->email ?? '',
            'author_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'author_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'locale'       => $data['locale'] ?? $user->locale,
            'status'       => self::STATUS_APPROVED,
        ]);
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
        $words = array_slice(preg_split('/\s+/u', trim($this->getAuthorName())) ?: [], 0, 2);

        return mb_strtoupper(implode('', array_map(fn (string $word) => mb_substr($word, 0, 1), $words)));
    }

    /**
     * Get the color of the author avatar, the same for every comment of the name.
     *
     * @return string A name of the dashboard palette, e.g. `blue`.
     */
    public function getColor(): string
    {
        $colors = ['blue', 'green', 'amber', 'purple', 'royal', 'red'];

        return $colors[crc32($this->getAuthorName()) % count($colors)];
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
