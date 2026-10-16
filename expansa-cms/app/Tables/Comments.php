<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Comment;
use App\Models\User;
use App\Post\Type;
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
     * Comments that are not deleted, by the filter and the order, the first 500, with the titles of their posts.
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
        $titles   = $this->titles($comments);
        $statuses = Comment::statuses();

        return array_map(function (array $comment) use ($titles, $statuses) {
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
                'actions'   => [['title' => t('Edit'), 'href' => $url]],
                'post'      => $titles[$comment['post_type']][$comment['post_id']] ?? '',
                'date'      => date('Y-m-d H:i', (int) strtotime($comment['created_at'])),
            ];
        }, $comments);
    }

    public function cells(): array
    {
        return [
            $this->cell('id')
                ->title('<input type="checkbox" u-bind="trigger">')
                ->fixedWidth('1.125rem')
                ->view('cb'),
            $this->cell('comment')
                ->title(t('Comment'))
                ->flexibleWidth('16rem')
                ->view('title'),
            $this->cell('author')
                ->title(t('Author'))
                ->fixedWidth('12rem')
                ->view('author'),
            $this->cell('status')
                ->title(t('Status'))
                ->fixedWidth('6rem')
                ->view('badge'),
            $this->cell('post')
                ->title(t('In reply to'))
                ->fixedWidth('8rem'),
            $this->cell('date')
                ->title(t('Date'))
                ->fixedWidth('8rem')
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
     * Titles of the posts the comments answer, one query per post type.
     *
     * @param array<int, array<string, mixed>> $comments
     * @return array<string, array<int, string>> Titles by post type and post ID.
     */
    private function titles(array $comments): array
    {
        $ids = [];
        foreach ($comments as $comment) {
            $ids[$comment['post_type']][] = $comment['post_id'];
        }

        $titles = [];
        foreach ($ids as $key => $postIds) {
            $type = Type::get((string) $key);
            if ($type !== null) {
                $posts        = Db::select($type->table, ['id [Int]', 'title'], ['id' => array_unique($postIds)]) ?? [];
                $titles[$key] = array_column($posts, 'title', 'id');
            }
        }

        return $titles;
    }
}
