<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Models\FieldGroup;
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
     * @return array<string, array{label: string, options: array<string, array{label: string, icon: string}>}>
     */
    private static function fieldTypes(): array
    {
        return [
            'basic'    => [
                'label'   => t('Basic'),
                'options' => [
                    'text'     => ['label' => t('Text'), 'icon' => 'ph ph-text-t'],
                    'number'   => ['label' => t('Number'), 'icon' => 'ph ph-hash'],
                    'password' => ['label' => t('Password'), 'icon' => 'ph ph-lock-key'],
                    'textarea' => ['label' => t('Textarea'), 'icon' => 'ph ph-text-align-left'],
                ],
            ],
            'choice'   => [
                'label'   => t('Choice'),
                'options' => [
                    'select'   => ['label' => t('Select'), 'icon' => 'ph ph-list'],
                    'checkbox' => ['label' => t('Checkbox'), 'icon' => 'ph ph-check-square'],
                    'radio'    => ['label' => t('Radio'), 'icon' => 'ph ph-radio-button'],
                ],
            ],
            'media'    => [
                'label'   => t('Media'),
                'options' => [
                    'image'    => ['label' => t('Image'), 'icon' => 'ph ph-user-circle'],
                    'media'    => ['label' => t('Media'), 'icon' => 'ph ph-image-square'],
                    'uploader' => ['label' => t('File Uploader'), 'icon' => 'ph ph-upload-simple'],
                    'file'     => ['label' => t('File'), 'icon' => 'ph ph-file'],
                    'gallery'  => ['label' => t('Gallery'), 'icon' => 'ph ph-images'],
                ],
            ],
            'advanced' => [
                'label'   => t('Advanced'),
                'options' => [
                    'editor'   => ['label' => t('Rich Text Editor'), 'icon' => 'ph ph-text-aa'],
                    'repeater' => ['label' => t('Repeater'), 'icon' => 'ph ph-rows'],
                ],
            ],
            'layout'   => [
                'label'   => t('Layout'),
                'options' => [
                    'header'   => ['label' => t('Heading'), 'icon' => 'ph ph-text-h'],
                    'divider'  => ['label' => t('Divider'), 'icon' => 'ph ph-minus'],
                    'details'  => ['label' => t('Details'), 'icon' => 'ph ph-caret-circle-down'],
                    'message'  => ['label' => t('Message'), 'icon' => 'ph ph-info'],
                    'progress' => ['label' => t('Progress'), 'icon' => 'ph ph-gauge'],
                ],
            ],
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
        foreach (Type::fetch() as $type) {
            $postTypes[$type->key] = $type->labelName;
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
                'trash'     => t('Trash'),
            ]],
            'user_role'   => ['label' => t('User Role'), 'options' => array_map(static fn ($role) => $role['name'], Role::all())],
        ];
    }
}
