<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\User;
use App\Post\Status;
use App\Post\Type;
use Expansa\Builders\Table;
use Expansa\Facades\Db;

/**
 * Items of a post type, the newest first: the title with the view, duplicate and delete actions,
 * the author, the status and the date; filtered by the status and the author. A type with a trash
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
        $posts = Db::select($this->type->table, ['id [Int]', 'title', 'author_id [Int]', 'status', 'created_at'], [
            ...$where,
            'ORDER' => ['id' => 'DESC'],
            'LIMIT' => 500,
        ]) ?? [];

        $slugs    = $this->slugs(array_column($posts, 'id'));
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
                'author'      => $author !== '' ? [
                    [
                        'title' => $author,
                        'href'  => url('dashboard/profile?id=' . $post['author_id']),
                    ],
                ] : [],
                'author_id'   => $post['author_id'],
                'status'      => [
                    'class' => $post['status'] === 'publish' ? 'badge t-green' : 'badge',
                    'value' => $statuses[$post['status']] ?? $post['status'],
                ],
                'post_status' => $post['status'],
                'date'        => date('Y-m-d H:i', (int) strtotime($post['created_at'])),
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
                ->fixedWidth('10rem')
                ->view('links'),
            $this->cell('status')
                ->title(t('Status'))
                ->fixedWidth('6rem')
                ->view('badge'),
            $this->cell('date')
                ->title(t('Date'))
                ->fixedWidth('8rem')
                ->sortable(),
        ];
    }

    public function headData(): array
    {
        $head = [
            'title'   => $this->type->labelNamePlural,
            'actions' => 'post-bulk',
        ];
        if (! $this->type->canTrash) {
            return $head;
        }

        $trash = (int) Db::count($this->type->table, ['deleted_at[!]' => null]);
        $views = [
            [
                'title'  => t('All (:count)', (int) Db::count($this->type->table, ['deleted_at' => null])),
                'href'   => self::url($this->type),
                'active' => ! $this->isTrash,
            ],
        ];

        if ($trash > 0 || $this->isTrash) {
            $views[] = [
                'title'  => t('Trash (:count)', $trash),
                'href'   => self::url($this->type, true),
                'active' => $this->isTrash,
            ];
        }

        if ($trash > 0 && $this->isTrash) {
            $views[] = [
                'title' => t('Empty Trash'),
                'click' => sprintf(
                    "confirm(%s) && \$ajax.post('post/empty-trash', %s)",
                    json_encode(t('Delete all items in the trash permanently?')),
                    json_encode(['post-type' => $this->type->key]),
                ),
            ];
        }

        return [...$head, 'views' => $views];
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
            'post_status' => [
                'label'   => t('Status'),
                'icon'    => 'ph ph-circle-dashed',
                'options' => Status::getStatuses(),
            ],
            'author_id'   => [
                'label'   => t('Author'),
                'icon'    => 'ph ph-user',
                'options' => User::options(),
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
     * @param string $slug Slug of the item, empty when it has none.
     * @return array<int, array<string, mixed>>
     */
    private function actions(int $id, string $slug): array
    {
        $item    = json_encode(['post-type' => $this->type->key, 'id' => $id]);
        $confirm = json_encode(t('Delete this item permanently?'));
        $actions = [];

        if ($this->type->public && $slug !== '') {
            $actions[] = [
                'href'       => url($slug),
                'class'      => 't-muted',
                'title'      => t('View'),
                'attributes' => ['target' => '_blank'],
            ];
        }

        $actions[] = [
            'href'       => '#',
            'class'      => 't-muted',
            'title'      => t('Duplicate'),
            'attributes' => ['@click.prevent' => "\$ajax.post('post/duplicate', $item)"],
        ];
        $actions[] = $this->type->canTrash ? [
            'href'       => '#',
            'class'      => 't-red',
            'title'      => t('Trash'),
            'attributes' => ['@click.prevent' => "\$ajax.post('post/delete', $item)"],
        ] : [
            'href'       => '#',
            'class'      => 't-red',
            'title'      => t('Delete'),
            'attributes' => ['@click.prevent' => "confirm($confirm) && \$ajax.post('post/delete', $item)"],
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
        $item    = json_encode(['post-type' => $this->type->key, 'id' => $id]);
        $confirm = json_encode(t('Delete this item permanently?'));

        return [
            [
                'href'       => '#',
                'class'      => 't-muted',
                'title'      => t('Restore'),
                'attributes' => ['@click.prevent' => "\$ajax.post('post/restore', $item)"],
            ],
            [
                'href'       => '#',
                'class'      => 't-red',
                'title'      => t('Delete Permanently'),
                'attributes' => ['@click.prevent' => "confirm($confirm) && \$ajax.post('post/delete', $item)"],
            ],
        ];
    }

    /**
     * Get the slugs of the items by their IDs.
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    private function slugs(array $ids): array
    {
        if (! $this->type->public || $ids === []) {
            return [];
        }

        $rows = Db::select('slugs', ['entity_id [Int]', 'slug'], [
            'entity_table' => $this->type->table,
            'entity_id'    => $ids,
        ]) ?? [];

        return array_column($rows, 'slug', 'entity_id');
    }
}
