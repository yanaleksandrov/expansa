<?php

declare(strict_types=1);

namespace App\Api\FieldGroups;

use App\Models\FieldGroup;
use Expansa\Debug\Error;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;
use Expansa\Http\Response;

/**
 * Every response here uses the `$ajax` fragment convention only (`data: [{target, notify,
 * redirect, ...}]`) - see dashboard/assets/js/youla-ajax.js's `applyFragment()`. The builder
 * UI calls `$ajax.post(...)` directly from its blade template, so there's nothing for a
 * client-side `.then()`/`.catch()` to read; a fragment's `notify`/`redirect` actions are all
 * the feedback the page gets; validation failures still get a 200 with an error `notify`
 * fragment for the same reason: `$ajax` only applies fragments from a successful response.
 */
final class FieldGroupsService
{
    /**
     * List every field group, newest first, with a `fieldsCount` summary for the sidebar.
     */
    public function index(): Response
    {
        $groups = array_map(
            fn (FieldGroup $group) => [
                'id'          => $group->id,
                'title'       => $group->title,
                'slug'        => $group->slug,
                'status'      => $group->status,
                'fieldsCount' => count($group->fields),
                'updatedAt'   => $group->updatedAt,
            ],
            FieldGroup::all()
        );

        return new Response()->json(['groups' => $groups]);
    }

    public function create(array $input): Response
    {
        $group = FieldGroup::create($this->prepare($input));

        if ($group instanceof Error) {
            return $this->notify($this->flatten($group), 'error');
        }

        return $this->notify(t('Field group created.'), 'success', '?group=' . $group->id);
    }

    public function update(array $input): Response
    {
        $id = Safe::absint($input['id'] ?? 0);
        if (! $id) {
            return $this->notify(t('Field group ID is missing.'), 'error');
        }

        $group = FieldGroup::find($id);
        if ($group instanceof Error) {
            return $this->notify($this->flatten($group), 'error');
        }

        $group = $group->update($this->prepare($input));
        if ($group instanceof Error) {
            return $this->notify($this->flatten($group), 'error');
        }

        return $this->notify(t('Field group updated.'));
    }

    public function delete(array $input): Response
    {
        $id = Safe::absint($input['id'] ?? 0);
        if (! $id) {
            return $this->notify(t('Field group ID is missing.'), 'error');
        }

        $group = FieldGroup::find($id);
        if ($group instanceof Error) {
            return $this->notify($this->flatten($group), 'error');
        }

        $group->delete();

        return $this->notify(t('Field group deleted.'), 'success', '/dashboard/field-groups');
    }

    /**
     * Normalize the raw request payload (title/status/location/fields, the latter two sent
     * as JSON strings by the builder UI) into the shape {@see FieldGroup} expects - `fields`
     * ends up in exactly the shape {@see \Expansa\Builders\Forms\Field::parse()} consumes.
     */
    private function prepare(array $input): array
    {
        $data = Safe::data($input, [
            'title'  => 'text',
            'status' => 'id:active',
        ])->apply();

        $location = Json::decode((string) ($input['location'] ?? '[]'), true);
        $fields   = Json::decode((string) ($input['fields'] ?? '[]'), true);

        $data['location'] = is_array($location) ? $location : [];
        $data['fields']   = array_map($this->normalizeField(...), is_array($fields) ? $fields : []);

        return $data;
    }

    /**
     * One field row from the builder UI (flat: type/label/name/required/placeholder/...)
     * into the canonical `Field::parse()` shape (attributes nested, options as an assoc array).
     */
    private function normalizeField(array $field): array
    {
        $attributes = [
            'required'    => ! empty($field['required']),
            'placeholder' => trim((string) ($field['placeholder'] ?? '')),
            'min'         => $field['min'] ?? '',
            'max'         => $field['max'] ?? '',
            'rows'        => $field['rows'] ?? '',
        ];
        $attributes = array_filter($attributes, static fn ($value) => $value !== '' && $value !== false);

        return [
            'type'        => Safe::id($field['type'] ?? ''),
            'name'        => Safe::name($field['name'] ?? ''),
            'label'       => trim((string) ($field['label'] ?? '')),
            'instruction' => trim((string) ($field['instruction'] ?? '')),
            'attributes'  => $attributes,
            'options'     => $this->parseOptions((string) ($field['options'] ?? '')),
        ];
    }

    /**
     * Parses one `value:Label` pair per line (same convention as the toolkit "Fields
     * builder" devtool) into an associative options array.
     */
    private function parseOptions(string $text): array
    {
        $options = [];
        foreach (preg_split('/\r?\n/', trim($text)) as $line) {
            if ($line === '') {
                continue;
            }

            [$value, $label] = array_pad(explode(':', $line, 2), 2, '');
            $options[trim($value)] = trim($label) !== '' ? trim($label) : trim($value);
        }

        return $options;
    }

    /**
     * Flattens an {@see Error}'s message - a plain string wrapped in an array for a
     * lookup failure, or a `field => messages[]` map from validation - into one string.
     */
    private function flatten(Error $error): string
    {
        $messages = [];
        array_walk_recursive($error->jsonSerialize()['message'] ?? [], function ($message) use (&$messages) {
            $messages[] = $message;
        });

        return $messages ? implode(' ', $messages) : t('Something went wrong.');
    }

    /**
     * A single `$ajax` response fragment: a notification, optionally followed by a
     * redirect once the notification has had time to appear.
     */
    private function notify(string $message, string $type = 'success', ?string $redirect = null): Response
    {
        $fragment = [
            'target' => 'body',
            'notify' => [$message, $type],
        ];

        if ($redirect !== null) {
            $fragment['redirect:600'] = $redirect;
        }

        return new Response()->json(['data' => [$fragment]]);
    }
}
