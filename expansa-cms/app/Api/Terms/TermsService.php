<?php

declare(strict_types=1);

namespace App\Api\Terms;

use App\Models\Option;
use App\Models\Term;
use App\Models\User;
use App\Post\Taxonomy;
use App\Support\Permalinks;
use Expansa\Facades\Db;
use Expansa\Http\Enums\Notice;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Support\Error;

/**
 * Terms of the taxonomies: the list and the editor of the terms change them, the post editor finds them.
 * Every change needs the capabilities of the taxonomy, see Taxonomy::$capabilities. The answers are `$ajax` fragments.
 *
 * @package App\Api
 */
final class TermsService
{
    /**
     * Terms found for a select of the post editor, the list stays short.
     */
    private const int LIMIT = 20;

    /**
     * Create a term from the form of the list and show the list again.
     *
     * @param Request  $request  POST: `taxonomy`, `name`, `slug`, `description`, `parent_id`.
     * @param Response $response
     * @return Response
     */
    public function create(Request $request, Response $response): Response
    {
        $taxonomy = self::taxonomy($request->getString('taxonomy'));
        if ($taxonomy === null) {
            return $response->notify(t('You are not allowed to do this.'), Notice::Error);
        }

        $term = Term::create([...$request->post, 'taxonomy' => $taxonomy->key]);
        if ($term instanceof Error) {
            return $response->notify(self::flatten($term), Notice::Error);
        }

        return $response->notify(t(':term created', $taxonomy->labelName), Notice::Success)->reload(600);
    }

    /**
     * Save a term from its editor.
     *
     * @param Request  $request  POST: `id`, `name`, `slug`, `description`, `parent_id`.
     * @param Response $response
     * @return Response
     */
    public function update(Request $request, Response $response): Response
    {
        $term = self::term($request);
        if ($term === null) {
            return $response->notify(t('Term not found'), Notice::Error);
        }

        $slug = $request->post['slug'] ?? null;
        $term = $term->update($request->post);
        if ($term instanceof Error) {
            return $response->notify(self::flatten($term), Notice::Error);
        }

        // the slug the editor shows becomes the saved one: made from the name or with a suffix of a taken one
        if ($slug !== null && (string) $slug !== $term->slug) {
            $response = $response->value('[name="slug"]', $term->slug);
        }

        return $response->notify(t(':term updated', Taxonomy::get($term->taxonomy)->labelName ?? ''), Notice::Success);
    }

    /**
     * Delete a term and open the list of its taxonomy.
     *
     * @param Request  $request  POST: `id`.
     * @param Response $response
     * @return Response
     */
    public function delete(Request $request, Response $response): Response
    {
        $term = self::term($request);
        if ($term === null) {
            return $response->notify(t('Term not found'), Notice::Error);
        }

        if (! $term->delete()) {
            return $response->notify(t('The default term cannot be deleted.'), Notice::Error);
        }

        $taxonomy = Taxonomy::get($term->taxonomy);

        return $response
            ->notify(t(':term deleted', $taxonomy->labelName ?? ''), Notice::Success)
            ->redirect(url('dashboard/' . $taxonomy?->url($request->getString('type'))), 600);
    }

    /**
     * Apply a bulk action of the list of the terms, `delete`, to the selected terms and reload the list.
     *
     * @param Request  $request  POST: `taxonomy`, `action`, `ids`: comma-separated term IDs.
     * @param Response $response
     * @return Response
     */
    public function bulk(Request $request, Response $response): Response
    {
        $taxonomy = self::taxonomy($request->getString('taxonomy'));
        $ids      = array_unique(array_filter(array_map(intval(...), explode(',', $request->getString('ids')))));
        if ($taxonomy === null || $ids === [] || $request->getString('action') !== 'delete') {
            return $response->notify(t('Select items and an action first.'));
        }

        $count = 0;
        foreach (Term::where(['id' => $ids, 'taxonomy' => $taxonomy->key])->find() as $term) {
            $count += (int) $term->delete();
        }

        return $response
            ->notify(t('Items changed: :count', $count), $count > 0 ? Notice::Success : Notice::Info)
            ->reload(600);
    }

    /**
     * Make a term the default one of its taxonomy, see Term::defaultId().
     *
     * @param Request  $request  POST: `id`.
     * @param Response $response
     * @return Response
     */
    public function setDefault(Request $request, Response $response): Response
    {
        $term = self::term($request);
        if ($term === null) {
            return $response->notify(t('Term not found'), Notice::Error);
        }

        Option::update("taxonomies.$term->taxonomy.default", $term->id);

        return $response->notify(t('Default term changed'), Notice::Success)->reload(600);
    }

    /**
     * Find the terms of a taxonomy by the name for a select of the post editor.
     *
     * @param Request  $request  POST: `taxonomy`, `s`, the text of the search.
     * @param Response $response
     * @return Response JSON `options`: `value` is the term ID, `text` the name.
     */
    public function search(Request $request, Response $response): Response
    {
        $where = [
            'taxonomy' => $request->getString('taxonomy'),
            'ORDER'    => ['name' => 'ASC'],
            'LIMIT'    => self::LIMIT,
        ];
        if ($request->getString('s') !== '') {
            $where['name[~]'] = $request->getString('s');
        }

        $options = [];
        foreach (Db::select('terms', ['id [Int]', 'name'], $where) ?: [] as $term) {
            $options[] = [
                'value' => (string) $term['id'],
                'text'  => $term['name'],
            ];
        }

        return $response->json(['options' => $options]);
    }

    /**
     * Answer the terms of a public taxonomy as a tree, children under their parents.
     *
     * @param Response $response
     * @param string   $taxonomy
     * @return Response
     */
    public function tree(Response $response, string $taxonomy): Response
    {
        $registered = Taxonomy::get($taxonomy);
        if ($registered === null || ! $registered->public) {
            return $response->json(['message' => t('Taxonomy not found')], 404);
        }

        $terms = Term::tree($taxonomy);
        $paths = Term::paths($taxonomy, array_column($terms, 'id'));

        // the tree lists a parent before its children, so a child finds its parent in the nodes
        $nodes = [];
        $roots = [];
        foreach ($terms as $term) {
            $nodes[$term['id']] = [
                'id'       => $term['id'],
                'name'     => $term['name'],
                'slug'     => $term['slug'],
                'url'      => isset($paths[$term['id']]) ? url(Permalinks::term($paths[$term['id']], $taxonomy)) : '',
                'count'    => $term['count'],
                'children' => [],
            ];
        }
        foreach (array_reverse($terms) as $term) {
            if ($term['depth'] > 0 && isset($nodes[$term['parent_id']])) {
                array_unshift($nodes[$term['parent_id']]['children'], $nodes[$term['id']]);
            } else {
                array_unshift($roots, $nodes[$term['id']]);
            }
        }

        return $response->json(['taxonomy' => $taxonomy, 'terms' => $roots]);
    }

    /**
     * Get a registered taxonomy the current user may change the terms of.
     *
     * @param string $key
     * @return Taxonomy|null
     */
    private static function taxonomy(string $key): ?Taxonomy
    {
        $taxonomy = Taxonomy::get($key);

        return $taxonomy?->canManage(User::current()) ? $taxonomy : null;
    }

    /**
     * Get the term of the request by `id`, of a taxonomy the current user may change.
     *
     * @param Request $request
     * @return Term|null
     */
    private static function term(Request $request): ?Term
    {
        $term = Term::find($request->getInt('id'));

        return $term instanceof Term && self::taxonomy($term->taxonomy) !== null ? $term : null;
    }

    /**
     * Join the messages of an error, a string or the messages of the fields, into one string.
     *
     * @param Error $error
     * @return string
     */
    private static function flatten(Error $error): string
    {
        $messages = [];
        $nested   = $error->messages;

        array_walk_recursive($nested, function ($message) use (&$messages) {
            $messages[] = $message;
        });

        return $messages ? implode(' ', $messages) : t('Something went wrong.');
    }
}
