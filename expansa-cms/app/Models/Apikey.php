<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Database\Model\HasTimestamps;

/**
 * Represents an API key entity with metadata and content information.
 *
 * @property int     $id         The primary identifier of the API key.
 * @property string  $uuid       The universally unique identifier (UUID) of the API key.
 * @property string  $title      The title or name of the API key.
 * @property string  $content    The content or description associated with the API key.
 * @property int     $authorId   The ID of the author or owner of the API key.
 * @property int     $parentId   The ID of the parent entity if this API key is hierarchical.
 * @property int     $comments   The number of comments related to this API key.
 * @property int     $views      The number of times this API key has been viewed.
 * @property int     $position   The position or sort order for this API key.
 * @property string  $status     The current status of the API key (e.g., active, inactive).
 * @property string  $discussion The discussion state (e.g., open, closed) associated with this API key.
 * @property string  $password   The password or protection token for the API key, if applicable.
 * @property string  $createdAt  The date and time when the API key was created.
 * @property string  $updatedAt  The date and time when the API key was last updated.
 * @property array   $comment    A list of users comments associated with the API key.
 * @property Field   $field      A dynamic meta field instance associated with the API key.
 */
class Apikey extends Model
{
    use HasTimestamps;
    use Model\HasFieldEav;

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table = 'api_keys';

    /**
     * One-to-many with Field: the foreign key column on api_keys_fields (the "many" side) that
     * points back to this API key (see Model\HasFieldEav).
     *
     * @var string
     */
    public string $fieldsForeignKey = 'api_key_id';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    protected array $fillable = [
        'title',
        'content',
        'author_id',
        'parent_id',
        'comments',
        'views',
        'position',
        'status',
        'discussion',
        'password',
    ];

    protected function createdAt(): Model\Attribute
    {
        return Model\Attribute::make(
            get: function ($value) {
                if (!is_string($value) || ($timestamp = strtotime($value)) === false) {
                    return '';
                }
                return date('j F, Y', $timestamp);
            }
        );
    }

    protected function updatedAt(): Model\Attribute
    {
        return $this->createdAt();
    }
}
