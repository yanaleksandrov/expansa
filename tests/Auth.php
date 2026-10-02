<?php

declare(strict_types=1);

use Expansa\Auth\Contracts\Permissions;
use Expansa\Auth\Contracts\Policy;
use Expansa\Auth\Contracts\Subject;
use Expansa\Auth\Exceptions\AccessDenied;
use Expansa\Auth\Exceptions\PolicyNotFound;
use Expansa\Auth\Manager;
use Expansa\Auth\Roles;
use Expansa\Facades\Auth;
use Expansa\Facades\Role;

// run: php tests/Auth.php
require_once __DIR__ . '/bootstrap.php';

// the application side: a subject, resources, a permission source and policies
final class Member implements Subject
{
    public function __construct(public int $id, public array $roles = [], public bool $banned = false) {}
}

interface Owned
{
    public int $ownerId { get; }
}

class Article implements Owned
{
    public function __construct(public int $ownerId, public string $status = 'draft', public bool $locked = false) {}
}

final class News extends Article {}

final class Comment implements Owned
{
    public function __construct(public int $ownerId) {}
}

// a custom source wrapping another one
final class CountedPermissions implements Permissions
{
    public int $calls = 0;

    public function __construct(private Permissions $permissions) {}

    public function has(?object $subject, string $permission): bool
    {
        $this->calls++;

        return $this->permissions->has($subject, $permission);
    }
}

final class ArticlePolicy implements Policy
{
    public static int $created = 0;

    public function __construct()
    {
        self::$created++;
    }

    public function can(?object $subject, string $ability, object $resource): bool
    {
        if (! $subject instanceof Member || $subject->banned) {
            return $ability === 'view' && $resource->status === 'published';
        }

        $owner = $resource->ownerId === $subject->id;

        return match ($ability) {
            'view'    => $resource->status === 'published' || $owner,
            // the owner edits an unlocked article, an editor edits any article except a published locked one
            'update'  => ($owner && ! $resource->locked) || (in_array('editor', $subject->roles, true) && ! ($resource->locked && $resource->status === 'published')),
            'publish' => in_array('editor', $subject->roles, true) && $resource->status === 'draft',
            default   => false,
        };
    }
}

final class OwnedPolicy implements Policy
{
    public function can(?object $subject, string $ability, object $resource): bool
    {
        return $subject instanceof Member && $resource->ownerId === $subject->id;
    }
}

$roles = new Roles();
$roles->add('author', 'Author', ['article.update']);
$roles->add('editor', 'Editor', ['article.update', 'article.publish']);

$author = new Member(1, ['author']);
$editor = new Member(2, ['editor']);
$banned = new Member(3, ['editor'], banned: true);

// without configuration
$auth = new Manager();
check('without configuration permissions are denied', ! $auth->allows($editor, 'article.update') && $auth->denies($editor, 'article.update'));
check('without configuration a resource has no policy', throws(fn () => $auth->can($editor, 'update', new Article(2)), PolicyNotFound::class));

// permissions
$permissions = new CountedPermissions($roles);
$auth->configure(permissions: $permissions);
check('allows() asks the permission source', $auth->allows($editor, 'article.publish') && $auth->allows($author, 'article.update'));
check('denies() is the opposite of allows()', $auth->denies($author, 'article.publish') && ! $auth->denies($editor, 'article.publish'));
check('a guest is passed as null', $auth->denies(null, 'article.update'));
check('unknown permissions are denied', $auth->denies($editor, 'article.delete'));

// policies
$auth->setPolicy(Article::class, ArticlePolicy::class);
check('a policy class is created on first use', ArticlePolicy::$created === 0);

$draft     = new Article(ownerId: 1);
$published = new Article(ownerId: 1, status: 'published');
$locked    = new Article(ownerId: 1, status: 'published', locked: true);

check('can() asks the policy of the resource class', $auth->can($author, 'update', $draft) && ArticlePolicy::$created === 1);
check('the policy instance is reused', $auth->can($editor, 'update', $draft) && ArticlePolicy::$created === 1);
check('cannot() is the opposite of can()', $auth->cannot($author, 'publish', $draft) && ! $auth->cannot($editor, 'publish', $draft));
check('ownership is decided by the policy', $auth->cannot(new Member(9, ['author']), 'update', $draft));
check('several conditions: locked and published', $auth->cannot($author, 'update', $locked) && $auth->cannot($editor, 'update', $locked) && $auth->can($editor, 'update', new Article(1, 'draft', true)));
check('guests and banned subjects only view published', $auth->can(null, 'view', $published) && $auth->cannot(null, 'view', $draft) && $auth->cannot($banned, 'update', $draft));
check('unknown abilities are denied by the policy', $auth->cannot($editor, 'delete', $draft));
check('permissions and policies are independent', $permissions->calls === 6);

// policy lookup
check('a subclass uses the policy of its parent', $auth->can($author, 'update', new News(1)));

$auth->setPolicy(Owned::class, new OwnedPolicy());
check('an interface policy covers its implementations', $auth->can($author, 'edit', new Comment(1)) && $auth->cannot($editor, 'edit', new Comment(1)));
check('a class policy wins over an interface one', $auth->cannot($author, 'edit', $draft));

$auth->setPolicy(News::class, new OwnedPolicy());
check('setPolicy() drops the policies found before', $auth->can($author, 'edit', new News(1)));

check('a missing policy is an error, not a denial', throws(fn () => $auth->can($author, 'update', new stdClass()), PolicyNotFound::class));
check('a policy class must implement the contract', throws(fn () => new Manager()->setPolicy(Article::class, stdClass::class)->can($author, 'update', $draft), InvalidArgumentException::class));

// authorize()
$denied = null;
try {
    $auth->authorize($author, 'article.publish');
} catch (AccessDenied $e) {
    $denied = $e;
}
check('authorize() throws for a denied permission', $denied?->ability === 'article.publish' && $denied->resource === null);

$denied = null;
try {
    $auth->authorize($author, 'update', $locked);
} catch (AccessDenied $e) {
    $denied = $e;
}
check('authorize() throws for a denied ability with its resource', $denied?->ability === 'update' && $denied->resource === $locked && str_contains($denied->getMessage(), Article::class));
check('authorize() passes when allowed', ! throws(fn () => $auth->authorize($editor, 'article.publish')) && ! throws(fn () => $auth->authorize($author, 'update', $draft)));
check('authorize() does not hide configuration errors', throws(fn () => $auth->authorize($author, 'update', new stdClass()), PolicyNotFound::class));

// errors of the permission source are not denials
$auth->configure(permissions: new class implements Permissions {
    public function has(?object $subject, string $permission): bool
    {
        throw new RuntimeException('storage is down');
    }
});
check('a failing permission source throws its own error', throws(fn () => $auth->allows($editor, 'article.update'), RuntimeException::class) && ! throws(fn () => $auth->allows($editor, 'x'), AccessDenied::class));

// configure()
$auth->configure(policies: [Article::class => new OwnedPolicy()]);
check('configure() replaces permissions and policies', $auth->permissions === null && $auth->denies($editor, 'article.update') && $auth->cannot($editor, 'update', $draft) && throws(fn () => $auth->can($author, 'edit', new Comment(1)), PolicyNotFound::class));

// roles
$registry = new Roles();
check('add() adds a role once', $registry->add('author', 'Author', ['read', 'read', 'post.edit']) && ! $registry->add('author', 'Other'));
check('get() returns the name and unique permissions', $registry->get('author') === ['name' => 'Author', 'permissions' => ['read', 'post.edit']] && $registry->get('ghost') === null);
check('add() copies the permissions of another role', $registry->add('writer', 'Writer', 'author') && $registry->hasPermission('writer', 'post.edit'));
check('copying from a missing role throws', throws(fn () => $registry->add('x', 'X', 'ghost'), InvalidArgumentException::class) && ! $registry->hasRole('x'));

$registry->grant('author', ['post.publish', 'read'])->revoke('author', 'read');
check('grant() and revoke() change one role', $registry->get('author')['permissions'] === ['post.edit', 'post.publish'] && $registry->hasPermission('writer', 'read'));
check('grant() and revoke() of a missing role throw', throws(fn () => $registry->grant('ghost', 'read'), InvalidArgumentException::class) && throws(fn () => $registry->revoke('ghost', 'read'), InvalidArgumentException::class));

$writer = new Member(5, ['writer', 'ghost']);
check('has() checks every role of the subject', $registry->has($writer, 'post.edit') && ! $registry->has($writer, 'post.publish'));
check('guests and subjects without roles have no permissions', ! $registry->has(null, 'read') && ! $registry->has(new stdClass(), 'read'));
check('forget() removes a role from its subjects', $registry->forget('writer') && ! $registry->forget('writer') && ! $registry->has($writer, 'post.edit'));
check('all() lists roles by name', array_keys($registry->all()) === ['author']);

// facade
Role::swap($roles);
Auth::configure(permissions: $roles, policies: [Article::class => ArticlePolicy::class]);
Role::grant('author', 'article.publish');
check('roles changed through Role are seen by Auth', Auth::allows($author, 'article.publish') && Role::hasRole('editor'));
check('the facade passes calls to the manager', Auth::allows($editor, 'article.update') && Auth::can($author, 'update', $draft) && throws(fn () => Auth::authorize(null, 'article.update'), AccessDenied::class));

exit($failures ? 1 : 0);
