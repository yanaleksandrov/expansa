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

        return $comment;
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

        $count = Db::count('comments', [
            'post_type'  => $postType,
            'post_id'    => $postId,
            'status'     => self::STATUS_APPROVED,
            'deleted_at' => null,
        ]);

        // the column is an unsigned SMALLINT
        Db::update($type->table, ['comments' => min((int) $count, 65535)], ['id' => $postId]);
    }
}
