<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;

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
 * @property string      $status      Comment status: pending, approved, spam, rejected, trash.
 * @property string      $content     Main body text of the comment.
 * @property int         $likes       Number of likes.
 * @property int         $dislikes    Number of dislikes.
 * @property int|null    $rating      Optional numeric rating (e.g., for reviews).
 * @property string      $createdAt   Creation timestamp.
 * @property string      $updatedAt   Last update timestamp.
 * @property string      $deletedAt   Item deleted timestamp.
 *
 * @package App\Models
 */
class Comment extends Model
{
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
}
