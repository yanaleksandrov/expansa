<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Dashboard\Pages\Values\Choices;
use App\Models\FieldGroup;
use App\Post\Taxonomy;
use App\Post\Type;
use Expansa\Facades\Role;
use Expansa\Http\Request;

/**
 * Custom Fields builder (ACF-style): field groups made of custom fields, with simple location rules
 * describing where a group applies.
 *
 * @package App\Dashboard
 */
final class FieldGroups
{
    /**
     * Data of the `field-groups` page: the groups, the one of `group` being edited, the field types
     * and the locations a group can apply to.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public static function data(Request $request): array
    {
        $groups  = FieldGroup::all();
        $groupId = $request->getInt('group');
        $current = array_find($groups, fn (FieldGroup $group) => $group->id === $groupId);

        $locations = self::locations();

        return [
            'groups'       => $groups,
            'groupId'      => $groupId,
            'current'      => $current,
            'fieldTypes'   => self::fieldTypes(),
            'locations'    => $locations,
            'valueOptions' => array_map(static fn ($location) => $location['options'], $locations),
            'state'        => [
                'id'     => $current->id ?? 0,
                'title'  => $current->title ?? '',
                'status' => $current->status ?? 'active',
                'fields' => $current->fields ?? [],
                'groups' => $current->location ?? [],
            ],
        ];
    }

    /**
     * Curated palette of field types a group can be built from. Deliberately narrower than the
     * full `Expansa\Builders\Form\Fields\*` registry: structural/internal types (layout-group,
     * layout-tab, submit, hidden, custom, builder) aren't things an editor picks ad hoc here.
     *
     * @return Choices[]
     */
    private static function fieldTypes(): array
    {
        return [
            'basic'    => new Choices(
                label: t('Basic'),
                options: [
                    'text'     => t('Text'),
                    'number'   => t('Number'),
                    'password' => t('Password'),
                    'textarea' => t('Textarea'),
                ],
            ),
            'choice'   => new Choices(
                label: t('Choice'),
                options: [
                    'select'   => t('Select', context: 'field type'),
                    'checkbox' => t('Checkbox'),
                    'radio'    => t('Radio'),
                ],
            ),
            'media'    => new Choices(
                label: t('Media'),
                options: [
                    'image'    => t('Image'),
                    'media'    => t('Media'),
                    'uploader' => t('File Uploader'),
                    'file'     => t('File'),
                    'gallery'  => t('Gallery'),
                ],
            ),
            'advanced' => new Choices(
                label: t('Advanced'),
                options: [
                    'editor'   => t('Rich Text Editor'),
                    'repeater' => t('Repeater'),
                ],
            ),
            'layout'   => new Choices(
                label: t('Layout'),
                options: [
                    'header'   => t('Heading'),
                    'divider'  => t('Divider'),
                    'details'  => t('Details'),
                    'message'  => t('Message'),
                    'progress' => t('Progress'),
                ],
            ),
        ];
    }

    /**
     * What can be picked in the "location" select and, per location, the options of the "value" select.
     *
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    private static function locations(): array
    {
        $postTypes = [];
        foreach (Type::filter() as $type) {
            $postTypes[$type->key] = $type->labelName;
        }

        $taxonomies = [];
        foreach (Taxonomy::filter() as $taxonomy) {
            $taxonomies[$taxonomy->key] = $taxonomy->labelName;
        }

        return [
            'post_type'   => ['label' => t('Post Type'), 'options' => $postTypes],
            'post_status' => ['label' => t('Post Status'), 'options' => [
                'publish'   => t('Published'),
                'pending'   => t('Pending Review'),
                'draft'     => t('Draft'),
                'protected' => t('Password Protected'),
                'private'   => t('Private'),
                'future'    => t('Scheduled'),
            ]],
            'taxonomy'    => ['label' => t('Taxonomy'), 'options' => $taxonomies],
            'user_role'   => ['label' => t('User Role'), 'options' => array_map(static fn ($role) => $role['name'], Role::all())],
        ];
    }
}
