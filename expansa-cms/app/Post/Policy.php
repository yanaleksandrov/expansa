<?php

declare(strict_types=1);

namespace App\Post;

use App\Models\Post;
use App\Models\User;
use Expansa\Access\Contracts\Policy as AccessPolicy;
use Expansa\Facades\Access;

/**
 * Rules of posts: an author edits, publishes and deletes their own posts with `types_*`, the posts
 * of others need `other_types_*`, private posts of others need `private_types_*` as well. A published
 * post is readable by anyone, a private one by its author and whoever may edit private posts.
 *
 * ```php
 * Access::authorize(User::current(), 'update', $post);
 * ```
 */
final class Policy implements AccessPolicy
{
    /**
     * Abilities and their permission suffix.
     */
    private const array ABILITIES = ['update' => 'edit', 'delete' => 'delete', 'publish' => 'publish'];

    /**
     * Whether the subject may do the ability with the post.
     *
     * @param object|null $subject
     * @param string      $ability  `read`, `update`, `delete` or `publish`.
     * @param object      $resource A Post.
     * @return bool
     */
    public function can(?object $subject, string $ability, object $resource): bool
    {
        if (! $resource instanceof Post) {
            return false;
        }

        $isOwn     = $subject instanceof User && $resource->authorId === $subject->id;
        $isPrivate = $resource->status === 'private';

        if ($ability === 'read') {
            return ! $isPrivate || $isOwn || Access::allows($subject, 'private_types_edit');
        }

        $action = self::ABILITIES[$ability] ?? null;
        if ($action === null || ! $subject instanceof User) {
            return false;
        }

        if ($isOwn) {
            return Access::allows($subject, "types_$action");
        }

        return Access::allows($subject, "other_types_$action")
            && (! $isPrivate || Access::allows($subject, "private_types_$action"));
    }
}
