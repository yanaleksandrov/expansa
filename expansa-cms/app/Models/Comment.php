<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Debug\Error;

/**
 * Represents a user comment on a post, including nested replies, author details,
 * status tracking, and reaction metrics.
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
 * @property string      $createdAt   Creation timestamp.
 * @property string      $updatedAt   Last update timestamp.
 * @property string|null $deletedAt   Item deleted timestamp.
 *
 * @package App\Models
 */
class Comment extends Model
{
    use Model\HasSanitizing;
    use Model\HasValidation;
    use Model\HasTimestamps;
    use Model\HasSoftDeletes;

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
    protected string $table = 'comments';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<int, string>
     */
    protected array $fillable = [
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

        return $comment instanceof Comment ? $comment : error('comment-find', t('Comment not found.'));
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

        $comment = new self()->fill($data);

        if (! $comment->isValid()) {
            return error('comment-add', $comment->getValidatorErrors());
        }

        if (! $comment->save() instanceof self) {
            return error('comment-add', t('Failed to save the comment to the database.'));
        }

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
        $this->fill($data);

        if (! $this->isValid()) {
            return error('comment-update', $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error('comment-update', t('Failed to save the comment to the database.'));
        }

        return $this;
    }
}
