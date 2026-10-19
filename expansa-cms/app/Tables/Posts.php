<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Post;
use App\Models\User;
use App\Post\Status;
use App\Post\Type;
use App\Support\Dates;
use App\Support\Permalinks;
use Expansa\Builders\Table;
use Expansa\Facades\Db;
use PDO;

/**
 * Items of a post type, the newest first: the title with the view, duplicate and delete actions,
 * the author, the status and the date; filtered by the status, the author and the search. A type with a trash
 * shows it as a separate list, see Models\Post::delete().
 *
 * @package App\Tables
 */
final class Posts extends Table
{
    public function __construct(

        /**
         * Type of the listed items.
         */
        public readonly Type $type,

        /**
         * Whether the list shows the items in the trash instead of the others.
         */
        public readonly bool $isTrash = false,

        /**
         * Status the items are filtered by, empty for all.
         */
        public private(set) string $status = '' {
            set => isset(Status::getStatuses()[$value]) ? $value : '';
        },

        /**
         * Text searched in the title and the content.
         */
        public readonly string $search = '',
    ) {
        parent::__construct();
    }

    /**
     * The latest 500 items of the type, of the trash or out of it, with their authors and, for a public type, links.
     *
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $where = $this->type->canTrash ? [$this->isTrash ? 'deleted_at[!]' : 'deleted_at' => null] : [];
        if ($this->status !== '') {
            $where['status'] = $this->status;
        }

        if ($this->search !== '') {
            $where['OR'] = [
                'title[~]'   => $this->search,
                'content[~]' => $this->search,
            ];
        }

        $posts = Db::select($this->type->table, ['id [Int]', 'title', 'author_id [Int]', 'status', 'created_at'], [
            ...$where,
            'ORDER' => ['id' => 'DESC'],
            'LIMIT' => 500,
        ]) ?? [];

        $slugs    = Post::paths($this->type->key, array_column($posts, 'id'));
        $authors  = User::options();
        $statuses = Status::getStatuses();

        return array_map(function (array $post) use ($slugs, $authors, $statuses) {
            $url    = url('dashboard/post?type=' . $this->type->key . '&id=' . $post['id']);
            $author = $authors[$post['author_id']] ?? '';

            return [
                'id'          => $post['id'],
                'title'       => $post['title'] !== '' ? $post['title'] : t('(no title)'),
                'url'         => $url,
                'actions'     => $this->isTrash
                    ? $this->trashActions($post['id'])
                    : $this->actions($post['id'], $slugs[$post['id']] ?? ''),
                'author'      => $author,
                'author_id'   => $post['author_id'],
                'initials'    => User::initials($author),
                'color'       => User::color($author),
                'status'      => [
                    'class' => $post['status'] === 'publish' ? 'badge t-green' : 'badge',
                    'value' => $statuses[$post['status']] ?? $post['status'],
                ],
                'post_status' => $post['status'],
                'date'        => Dates::format((string) $post['created_at']),
            ];
        }, $posts);
    }

    public function cells(): array
    {
        return [
            $this->cell('id')
                ->title('<input type="checkbox" u-bind="trigger">')
                ->fixedWidth('1.125rem')
                ->view('cb'),
            $this->cell('title')
                ->title(t('Title'))
                ->flexibleWidth('16rem')
                ->sortable()
                ->view('title'),
            $this->cell('author')
                ->title(t('Author'))
                ->fixedWidth('14rem')
                ->view('author'),
            $this->cell('status')
                ->title(t('Status'))
                ->fixedWidth('7rem')
                ->view('badge'),
            $this->cell('date')
                ->title(t('Date'))
                ->fixedWidth('7rem')
                ->sortable(),
        ];
    }

    public function headData(): array
    {
        $counts = $this->counts();
        $empty  = [];
        $parts  = [
            '' => [
                'label' => t('All') . ' (' . array_sum($counts) . ')',
                'query' => [],
            ],
        ];

        foreach (Status::getStatuses() as $key => $label) {
            if (isset($counts[$key]) || $this->status === $key) {
                $parts[$key] = [
                    'label' => $label . ' (' . ($counts[$key] ?? 0) . ')',
                    'query' => ['status' => $key],
                ];
            }
        }

        if ($this->type->canTrash) {
            $trash = (int) Db::count($this->type->table, ['deleted_at[!]' => null]);
            if ($trash > 0 || $this->isTrash) {
                $parts['trash'] = [
                    'label' => t('Trash') . " ($trash)",
                    'query' => ['view' => 'trash'],
                ];
            }

            if ($trash > 0 && $this->isTrash) {
                $empty = [
                    'endpoint' => 'post/empty-trash',
                    'data'     => ['post-type' => $this->type->key],
                ];
            }
        }

        return [
            'title' => $this->type->labelNamePlural,
            'parts' => $parts,
            'part'  => $this->isTrash ? 'trash' : $this->status,
            'find'  => t('Search in title and content'),
            'empty' => $empty,
        ];
    }

    /**
     * Duplicate and move to the trash, or restore and delete permanently in the trash; a type without
     * a trash deletes at once.
     *
     * @return array<string, mixed>
     */
    public function bulk(): array
    {
        $actions = match (true) {
            $this->isTrash        => [
                'restore' => t('Restore'),
                'delete'  => t('Delete Permanently'),
            ],
            $this->type->canTrash => [
                'duplicate' => t('Duplicate'),
                'trash'     => t('Move to Trash'),
            ],
            default               => [
                'duplicate' => t('Duplicate'),
                'delete'    => t('Delete Permanently'),
            ],
        };

        return [
            'endpoint' => 'post/bulk',
            'actions'  => $actions,
            'confirm'  => ['delete'],
            'data'     => ['post-type' => $this->type->key],
        ];
    }

    /**
     * Count the items out of the trash by status, one query.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        $where  = $this->type->canTrash ? ' WHERE deleted_at IS NULL' : '';
        $counts = Db::query("SELECT status, COUNT(*) FROM <{$this->type->table}>$where GROUP BY status")
            ?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];

        return array_map(intval(...), $counts);
    }

    /**
     * Get the URL of the list of a post type, or of its trash.
     *
     * @param Type $type
     * @param bool $trash
     * @return string
     */
    public static function url(Type $type, bool $trash = false): string
    {
        return url('dashboard/edit?table=' . $type->key . ($trash ? '&view=trash' : ''));
    }

    public function filters(): array
    {
        return [
            'author_id'   => [
                'label'   => t('Author'),
                'icon'    => 'ph ph-user',
                'param'   => 'author',
                'options' => User::options(),
            ],
            'months'   => [
                'label'   => t('Months'),
                'icon'    => 'ph ph-timer',
                'options' => Status::getStatuses(),
            ],
        ];
    }

    public function notFoundData(): array
    {
        if ($this->isTrash) {
            return [
                'title'       => t('The trash is empty'),
                'description' => t('Deleted items stay here until you delete them permanently.'),
                'buttons'     => [
                    [
                        'label' => t('Show All :items', $this->type->labelNamePlural),
                        'url'   => self::url($this->type),
                    ],
                ],
            ];
        }

        if ($this->status !== '' || $this->search !== '') {
            return [
                'title'       => t('No :items found', $this->type->labelNamePlural),
                'description' => t('Nothing matches the filter.'),
            ];
        }

        return [
            'title'       => t('No :items found', $this->type->labelNamePlural),
            'description' => t("You don't have any items yet. Add them manually or import them from a CSV file."),
            'buttons'     => [
                ...($this->type->canCreate ? [
                    [
                        'label' => '<i class="ph ph-plus"></i> ' . $this->type->labelAdd,
                        'url'   => url('dashboard/post?type=' . $this->type->key),
                    ],
                ] : []),
                [
                    'label' => '<i class="ph ph-download-simple"></i> ' . t('Import from file'),
                    'url'   => url('dashboard/import'),
                ],
            ],
        ];
    }

    /**
     * Get the links of the actions under the title of an item: view only for an item with a public link,
     * delete to the trash when the type has one, permanently otherwise.
     *
     * @param int    $id
     * @param string $slug Path of the item in its URL, empty when it has none.
     * @return array<int, array<string, mixed>>
     */
    private function actions(int $id, string $slug): array
    {
        $item    = ['post-type' => $this->type->key, 'id' => $id];
        $actions = [];

        if ($this->type->public && $slug !== '') {
            $actions[] = [
                'href'       => url(Permalinks::post($slug, $id, $this->type->key)),
                'class'      => 't-muted',
                'title'      => t('View'),
                'attributes' => ['target' => '_blank'],
            ];
        }

        $actions[] = [
            'href'       => '#',
            'class'      => 't-muted',
            'title'      => t('Duplicate'),
            'attributes' => self::action('post/duplicate', $item),
        ];
        $actions[] = $this->type->canTrash ? [
            'href'       => '#',
            'class'      => 't-red',
            'title'      => t('Trash', context: 'action'),
            'attributes' => self::action('post/delete', $item),
        ] : [
            'href'       => '#',
            'class'      => 't-red',
            'title'      => t('Delete'),
            'attributes' => self::action('post/delete', $item, 'deleteItem'),
        ];

        return $actions;
    }

    /**
     * Get the links of the actions under the title of an item in the trash: restore and delete permanently.
     *
     * @param int $id
     * @return array<int, array<string, mixed>>
     */
    private function trashActions(int $id): array
    {
        $item = ['post-type' => $this->type->key, 'id' => $id];

        return [
            [
                'href'       => '#',
                'class'      => 't-muted',
                'title'      => t('Restore'),
                'attributes' => self::action('post/restore', $item),
            ],
            [
                'href'       => '#',
                'class'      => 't-red',
                'title'      => t('Delete Permanently'),
                'attributes' => self::action('post/delete', $item, 'deleteItem'),
            ],
        ];
    }

}
