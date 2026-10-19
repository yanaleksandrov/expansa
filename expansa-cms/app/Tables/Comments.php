<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Post\Type;
use App\Support\Dates;
use App\Support\Permalinks;
use Expansa\Builders\Table;
use Expansa\Facades\Db;
use PDO;

final class Comments extends Table
{
    public function __construct(

        /**
         * Status the rows are filtered by, empty for all.
         */
        public private(set) string $status = '' {
            set => isset(Comment::statuses()[$value]) ? $value : '';
        },

        /**
         * Whether the list shows the comments in the trash instead of the others.
         */
        public readonly bool $isTrash = false,

        /**
         * Text searched in the comment, the name and the email of the author.
         */
        public readonly string $search = '',

        /**
         * Whether the oldest rows go first, the newest by default.
         */
        public readonly bool $isAscending = false,
    ) {
        parent::__construct();
    }

    /**
     * Comments of the trash or out of it, by the filter and the order, the first 500, with the posts they answer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $columns  = [
            'id [Int]',
            'post_id [Int]',
            'post_type',
            'author_id [Int]',
            'author_name',
            'author_email',
            'status',
            'content',
            'created_at',
        ];
        $comments = Db::select('comments', $columns, [
            ...$this->where(),
            'ORDER' => ['id' => $this->isAscending ? 'ASC' : 'DESC'],
            'LIMIT' => 500,
        ]) ?? [];
        $posts    = $this->posts($comments);
        $statuses = Comment::statuses();

        return array_map(function (array $comment) use ($posts, $statuses) {
            $url    = url('dashboard/comment?id=' . $comment['id']);
            $author = $comment['author_name'] ?: t('Anonymous');
            $post   = $posts[$comment['post_type']][$comment['post_id']] ?? null;
            if ($post !== null && $post['view'] !== '' && $comment['status'] === Comment::STATUS_APPROVED) {
                $post['view'] .= '#comment-' . $comment['id'];
            }

            return [
                'id'        => $comment['id'],
                'author'    => $author,
                'initials'  => User::initials($author),
                'color'     => User::color($author),
                'author_id' => $comment['author_id'],
                'email'     => $comment['author_email'],
                'comment'   => mb_strimwidth(trim(strip_tags($comment['content'])), 0, 120, '…'),
                'url'       => $this->isTrash ? '#' : $url,
                'status'      => [
                    'class' => '',
                    'value' => $statuses[$comment['status']] ?? $comment['status'],
                ],
                'actions'   => $this->actions($comment['id'], $comment['status']),
                'post'      => $post,
                'date'      => Dates::format((string) $comment['created_at']),
            ];
        }, $comments);
    }

    /**
     * Quick links of a row: moderation and deletion, or those of the trash. They work in the background,
     * then the rows are filtered again, so a comment leaves a list of another status or disappears.
     *
     * @param int    $id
     * @param string $status Current status.
     * @return array<int, array<string, mixed>>
     */
    private function actions(int $id, string $status): array
    {
        if ($this->isTrash) {
            return $this->trashActions($id);
        }

        $actions = [];
        foreach (Comment::moderationActions($status) as $value => $action) {
            $payload   = json_encode(['id' => $id, 'status' => $value]);
            $actions[] = [
                'title'      => $action['label'],
                'href'       => '#',
                'class'      => $value === Comment::STATUS_APPROVED ? 't-green' : 't-muted',
                'attributes' => ['@click.prevent' => "\$ajax.post('comments/status', $payload).then(() => applyFilters())"],
            ];
        }

        $payload   = json_encode(['id' => $id, 'from' => 'list']);
        $actions[] = [
            'title'      => t('Delete'),
            'href'       => '#',
            'class'      => 't-red',
            'attributes' => ['@click.prevent' => "\$ajax.post('comments/delete', $payload).then(() => applyFilters())"],
        ];

        return $actions;
    }

    /**
     * Quick links of a row in the trash: restore and delete permanently, then the rows are filtered again.
     *
     * @param int $id
     * @return array<int, array<string, mixed>>
     */
    private function trashActions(int $id): array
    {
        $payload = json_encode(['id' => $id]);
        $delete  = "confirm(texts.deleteItem) && \$ajax.post('comments/force-delete', $payload).then(() => applyFilters())";

        return [
            [
                'title'      => t('Restore'),
                'href'       => '#',
                'class'      => 't-muted',
                'attributes' => ['@click.prevent' => "\$ajax.post('comments/restore', $payload).then(() => applyFilters())"],
            ],
            [
                'title'      => t('Delete Permanently'),
                'href'       => '#',
                'class'      => 't-red',
                'attributes' => ['@click.prevent' => $delete],
            ],
        ];
    }

    public function cells(): array
    {
        return [
            $this->cell('id')
                ->title('<input type="checkbox" u-bind="trigger">')
                ->fixedWidth('1.125rem')
                ->view('cb'),
            $this->cell('author')
                ->title(t('Author'))
                ->fixedWidth('14rem')
                ->view('author'),
            $this->cell('comment')
                ->title(t('Comment'))
                ->flexibleWidth('16rem')
                ->view('title'),
            $this->cell('status')
                ->title(t('Status'))
                ->fixedWidth('7rem')
                ->view('badge'),
            $this->cell('post')
                ->title(t('In reply to'))
                ->fixedWidth('12rem')
                ->view('post'),
            $this->cell('date')
                ->title(t('Date'))
                ->fixedWidth('7rem')
                ->sortable(),
        ];
    }

    public function headData(): array
    {
        $counts   = $this->counts();
        $statuses = ['' => t('All') . ' (' . array_sum($counts) . ')'];

        foreach (Comment::statuses() as $key => $label) {
            $statuses[$key] = $label . ' (' . ($counts[$key] ?? 0) . ')';
        }

        $empty = '';
        $trash = (int) Db::count('comments', ['deleted_at[!]' => null]);
        if ($trash > 0 || $this->isTrash) {
            $statuses['trash'] = t('Trash') . " ($trash)";
        }

        if ($trash > 0 && $this->isTrash) {
            $empty = "confirm(texts.emptyTrash) && \$ajax.post('comments/empty-trash', {})";
        }

        return [
            'title'    => t('Comments'),
            'statuses' => $statuses,
            'status'   => $this->isTrash ? 'trash' : $this->status,
            'find'     => t('Search comments'),
            'empty'    => $empty,
        ];
    }

    /**
     * Set a status or move to the trash, or restore and delete permanently in the trash.
     *
     * @return array<string, mixed>
     */
    public function bulk(): array
    {
        $actions = $this->isTrash
            ? [
                'restore'      => t('Restore'),
                'force-delete' => t('Delete Permanently'),
            ]
            : [
                ...array_map(fn (string $label) => t('Mark as :status', $label), Comment::statuses()),
                'delete' => t('Delete'),
            ];

        return [
            'endpoint' => 'comments/bulk',
            'actions'  => $actions,
            'confirm'  => ['force-delete'],
        ];
    }

    public function filters(): array
    {
        return [
            'author_id' => [
                'label'   => t('Author'),
                'icon'    => 'ph ph-user',
                'param'   => 'author',
                'options' => User::options(),
            ],
        ];
    }

    public function notFoundData(): array
    {
        if ($this->isTrash) {
            return [
                'title'       => t('The trash is empty'),
                'description' => t('Deleted comments stay here until you delete them permanently.'),
                'buttons'     => [
                    [
                        'label' => t('Show All Comments'),
                        'url'   => url('dashboard/edit?table=comments'),
                    ],
                ],
            ];
        }

        if ($this->status !== '' || $this->search !== '') {
            return [
                'title'       => t('No comments found'),
                'description' => t('Nothing matches the filter.'),
                'buttons'     => [
                    [
                        'label' => t('Show All Comments'),
                        'url'   => url('dashboard/edit?table=comments'),
                    ],
                ],
            ];
        }

        return [
            'title'       => t('No comments found'),
            'description' => t("Don't worry, they will appear as soon as someone leaves a comment."),
        ];
    }

    /**
     * Count the comments that are not deleted by status, for the filter.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        $counts = Db::query('SELECT status, COUNT(*) FROM <comments> WHERE deleted_at IS NULL GROUP BY status')
            ?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];

        return array_map(intval(...), $counts);
    }

    /**
     * Conditions of the filter.
     *
     * @return array<string, mixed>
     */
    private function where(): array
    {
        $where = [$this->isTrash ? 'deleted_at[!]' : 'deleted_at' => null];
        if ($this->status !== '') {
            $where['status'] = $this->status;
        }

        if ($this->search !== '') {
            $where['OR'] = [
                'content[~]'      => $this->search,
                'author_name[~]'  => $this->search,
                'author_email[~]' => $this->search,
            ];
        }

        return $where;
    }

    /**
     * Posts the comments answer: the title, the links to the editor and to the page, the number of the
     * approved comments kept in the post; per post type one query besides the ones of Post::paths().
     *
     * @param array<int, array<string, mixed>> $comments
     * @return array<string, array<int, array{title: string, edit: string, view: string, count: int}>> By post type and post ID.
     */
    private function posts(array $comments): array
    {
        $ids = [];
        foreach ($comments as $comment) {
            $ids[$comment['post_type']][$comment['post_id']] = (int) $comment['post_id'];
        }

        $result = [];
        foreach ($ids as $key => $postIds) {
            $type = Type::get((string) $key);
            if ($type === null) {
                continue;
            }

            $paths = Post::paths($type->key, $postIds);
            foreach (Db::select($type->table, ['id [Int]', 'title', 'comments [Int]'], ['id' => array_values($postIds)]) ?? [] as $post) {
                $path = isset($paths[$post['id']]) ? Permalinks::post($paths[$post['id']], $post['id'], $type->key) : '';

                $result[$key][$post['id']] = [
                    'title' => $post['title'] !== '' ? $post['title'] : t('(no title)'),
                    'edit'  => url('dashboard/post?type=' . $type->key . '&id=' . $post['id'] . '&tab=comments'),
                    'view'  => $path === '' ? '' : url($path),
                    'count' => $post['comments'],
                ];
            }
        }

        return $result;
    }
}
