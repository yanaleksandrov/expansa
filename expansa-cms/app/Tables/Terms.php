<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\Term;
use App\Post\Taxonomy;
use App\Support\Permalinks;
use Expansa\Builders\Table;
use Expansa\Builders\Table\Queried;
use Expansa\Builders\Table\Values\Bulk;
use Expansa\Builders\Table\Values\Code;
use Expansa\Builders\Table\Values\Link;
use Expansa\Builders\Table\Values\Title;
use Expansa\Facades\Db;

/**
 * Terms of a taxonomy: the name with the actions, the slug and the number of the posts linking to them.
 * A hierarchical taxonomy is shown as a tree, a parent before its children, else the terms are sorted by name;
 * a search finds the terms by the name or the slug and shows them by name.
 *
 * @package App\Tables
 */
final class Terms extends Table implements Queried
{
    public function __construct(

        /**
         * Taxonomy of the listed terms.
         */
        public readonly Taxonomy $taxonomy,

        /**
         * Key of the post type whose menu the list is under, its posts are opened by the counts.
         */
        public readonly string $type = '',

        /**
         * Text searched in the name and the slug.
         */
        public readonly string $search = '',
    ) {
        parent::__construct();
    }

    /**
     * Terms of the current page; the tree is read whole to put the parents before the children.
     *
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        if ($this->taxonomy->hierarchical && $this->search === '') {
            $perPage = $this->perPage();
            $terms   = Term::tree($this->taxonomy->key);
            $terms   = $perPage > 0 ? array_slice($terms, ($this->page() - 1) * $perPage, $perPage) : $terms;
        } else {
            $columns = ['id [Int]', 'name', 'slug', 'parent_id [Int]', 'count [Int]'];
            $terms   = Db::select('terms', $columns, $this->paged($this->where(), ['name' => 'ASC', 'id' => 'ASC'])) ?: [];
        }

        $default = Term::defaultId($this->taxonomy->key);
        $paths   = $this->taxonomy->public ? Term::paths($this->taxonomy->key, array_column($terms, 'id')) : [];
        $type    = in_array($this->type, $this->taxonomy->types, true) ? $this->type : '';

        return array_map(function (array $term) use ($default, $paths, $type) {
            $name = $term['id'] === $default ? t(':name — Default', $term['name']) : $term['name'];

            return [
                'id'    => $term['id'],
                'title' => new Title(
                    text: str_repeat('— ', $term['depth'] ?? 0) . $name,
                    url: url('dashboard/term?id=' . $term['id'] . ($type !== '' ? "&type=$type" : '')),
                    actions: $this->actions($term['id'], $paths[$term['id']] ?? '', $term['id'] === $default),
                ),
                'slug'  => new Code($term['slug']),
                'count' => new Link(
                    title: (string) $term['count'],
                    url: $type !== '' ? url("dashboard/edit?table=$type&{$this->taxonomy->key}=" . $term['id']) : '',
                ),
            ];
        }, $terms);
    }

    public function count(): int
    {
        return (int) Db::count('terms', $this->where());
    }

    /**
     * Conditions of the list: the taxonomy and the search in the name and the slug.
     *
     * @return array<string, mixed>
     */
    private function where(): array
    {
        $where = ['taxonomy' => $this->taxonomy->key];
        if ($this->search !== '') {
            $where['OR'] = [
                'name[~]' => $this->search,
                'slug[~]' => $this->search,
            ];
        }

        return $where;
    }

    public function cells(): array
    {
        return [
            $this->cell('id')
                ->title('<input type="checkbox" u-bind="trigger">')
                ->fixedWidth('1.125rem')
                ->view('cb'),
            $this->cell('title')
                ->title(t('Name'))
                ->flexibleWidth('12rem')
                ->view('title'),
            $this->cell('slug')
                ->title(t('Slug'))
                ->flexibleWidth('8rem')
                ->view('code'),
            $this->cell('count')
                ->title(t('Count'))
                ->fixedWidth('4rem')
                ->view('count'),
        ];
    }

    public function headData(): array
    {
        return [
            'title' => $this->taxonomy->labelNamePlural,
            'find'  => $this->taxonomy->labelSearch,
        ];
    }

    /**
     * Delete the selected terms; the default one stays, see Term::delete().
     *
     * @return Bulk
     */
    public function bulk(): Bulk
    {
        return new Bulk(
            endpoint: 'terms/bulk',
            actions: ['delete' => t('Delete Permanently')],
            confirm: ['delete'],
            data: ['taxonomy' => $this->taxonomy->key],
        );
    }

    public function notFoundData(): array
    {
        if ($this->search !== '') {
            return [
                'title'       => t('No :items found', $this->taxonomy->labelNamePlural),
                'description' => t('Nothing matches the filter.'),
            ];
        }

        return [
            'title'       => t('No :items found', $this->taxonomy->labelNamePlural),
            'description' => t('Add the first one with the form.'),
        ];
    }

    /**
     * Get the links of the actions under the name of a term: view for a term with a page,
     * make default in a hierarchical taxonomy and delete, both but for the default term.
     *
     * @param int    $id
     * @param string $path      Path of the term in its URL, empty when it has none.
     * @param bool   $isDefault
     * @return Link[]
     */
    private function actions(int $id, string $path, bool $isDefault): array
    {
        $actions = [];
        if ($path !== '') {
            $actions[] = new Link(
                title: t('View'),
                url: url(Permalinks::term($path, $this->taxonomy->key)),
                class: 't-muted',
                attributes: ['target' => '_blank'],
            );
        }

        if ($isDefault) {
            return $actions;
        }

        if ($this->taxonomy->hierarchical) {
            $actions[] = new Link(
                title: t('Make Default'),
                class: 't-muted',
                attributes: self::action('terms/set-default', ['id' => $id]),
            );
        }

        $actions[] = new Link(
            title: t('Delete'),
            class: 't-red',
            attributes: self::action('terms/delete', ['id' => $id, 'type' => $this->type], 'deleteItem'),
        );

        return $actions;
    }
}
