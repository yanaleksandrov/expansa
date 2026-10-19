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
     * Comments that are not deleted, by the filter and the order, the first 500, with the posts they answer.
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

            return [
                'id'        => $comment['id'],
                'author'    => $author,
                'initials'  => User::initials($author),
                'color'     => User::color($author),
                'author_id' => $comment['author_id'],
                'email'     => $comment['author_email'],
                'comment'   => mb_strimwidth(trim(strip_tags($comment['content'])), 0, 120, '…'),
                'url'       => $url,
                'status'      => [
                    'class' => '',
                    'value' => $statuses[$comment['status']] ?? $comment['status'],
                ],
                'actions'   => [['title' => t('Edit'), 'href' => $url], ...$this->moderationActions($comment['id'], $comment['status'])],
                'post'      => $posts[$comment['post_type']][$comment['post_id']] ?? null,
                'date'      => Dates::format((string) $comment['created_at']),
            ];
        }, $comments);
    }

    /**
     * Quick moderation links of a row: they change the status in the background, then the rows are
     * filtered again, so a comment leaves a list of another status.
     *
     * @param int    $id
     * @param string $status Current status.
     * @return array<int, array<string, mixed>>
     */
    private function moderationActions(int $id, string $status): array
    {
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

        return $actions;
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
                ->fixedWidth('12rem')
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

        return [
            'title'    => t('Comments'),
            'actions'  => 'comments-actions',
            'statuses' => $statuses,
            'status'   => $this->status,
            'find'     => t('Search comments'),
        ];
    }

    public function filters(): array
    {
        return [
            'author_id' => [
                'label'   => t('Author'),
                'icon'    => 'ph ph-user',
                'options' => User::options(),
            ],
        ];
    }

    public function notFoundData(): array
    {
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
        $where = ['deleted_at' => null];
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
