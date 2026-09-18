<?php

use App\Models\FieldGroup;
use App\Post\Type;
use App\User\Roles;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;

/**
 * Custom Fields builder (ACF-style): create field groups made of custom fields, with
 * simple location rules describing where a group applies.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/field-groups.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

/**
 * Curated palette of field types a group can be built from. Deliberately narrower than the
 * full `Expansa\Builders\Forms\Fields\*` registry: structural/internal types (layout-group,
 * layout-tab, submit, hidden, custom, builder) aren't things an editor picks ad hoc here.
 */
$fieldTypes = [
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

/**
 * What can be picked in the "location" select, and - per location - what the "value"
 * select's options are. Shared with `form/builder.blade.php` (a plain markup partial
 * embedded below, not its own component - it renders into this page's own `builder`
 * scope, which is why the data lives here rather than in that partial).
 */
$postTypes = [];
foreach (Type::fetch() as $type) {
    $postTypes[$type->key] = $type->labelName;
}

$postStatuses = [
    'publish'   => t('Published'),
    'pending'   => t('Pending Review'),
    'draft'     => t('Draft'),
    'protected' => t('Password Protected'),
    'private'   => t('Private'),
    'future'    => t('Scheduled'),
    'trash'     => t('Trash'),
];

$roles = array_map(static fn ($role) => $role['name'], Roles::get());

$locations = [
    'post_type'   => ['label' => t('Post Type'), 'options' => $postTypes],
    'post_status' => ['label' => t('Post Status'), 'options' => $postStatuses],
    'user_role'   => ['label' => t('User Role'), 'options' => $roles],
];

$valueOptions = array_map(static fn ($location) => $location['options'], $locations);

$groups  = FieldGroup::all();
$groupId = Safe::absint($_GET['group'] ?? 0);
$current = null;
foreach ($groups as $group) {
    if ($group->id === $groupId) {
        $current = $group;
        break;
    }
}

$state = [
    'id'     => $current->id ?? 0,
    'title'  => $current->title ?? '',
    'status' => $current->status ?? 'active',
    'fields' => $current->fields ?? [],
    'groups' => $current->location ?? [],
];
?>
<div class="expansa-main">
    <div class="field-groups" u-data="builder"
         data-state="<?php echo Safe::attribute(Json::encode($state)); ?>"
         data-value-options="<?php echo Safe::attribute(Json::encode($valueOptions)); ?>">
        <div class="field-groups__side">
            <div class="field-groups__header">
                <h4><?php echo t('Custom Fields'); ?></h4>
                <a class="btn btn--sm btn--primary" href="<?php echo url('/dashboard/field-groups'); ?>">
                    <i class="ph ph-plus"></i> <?php echo t('Add New'); ?>
                </a>
            </div>
            <div class="field-groups__list">
                <?php if ($groups) : ?>
                    <?php foreach ($groups as $group) : ?>
                        <a class="field-groups__item <?php echo $group->id === $groupId ? 'active' : ''; ?>"
                           href="<?php echo url('/dashboard/field-groups?group=' . $group->id); ?>">
                            <span><?php echo Safe::html($group->title); ?></span>
                            <span class="badge badge--<?php echo $group->status === 'active' ? 'green' : 'muted'; ?>">
                                <?php echo count($group->fields); ?> <?php echo t('fields'); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="t-muted fs-13"><?php echo t('No field groups yet.'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="field-groups__main">
            <form class="dg g-7" @submit.prevent="$ajax.post(id ? 'field-groups/update' : 'field-groups/create', {location: JSON.stringify(groups), fields: JSON.stringify(fields)})">
                <input type="hidden" name="id" u-prop="id">

                <div class="field">
                    <div class="field-label"><?php echo t('Title'); ?></div>
                    <label class="field-item">
                        <input type="text" name="title" u-prop="title" placeholder="<?php echo t('e.g. Product Details'); ?>" required>
                    </label>
                </div>

                <div class="field">
                    <div class="field-label"><?php echo t('Status'); ?></div>
                    <label class="field-item">
                        <select name="status" u-prop="status" u-select>
                            <option value="active"><?php echo t('Active'); ?></option>
                            <option value="inactive"><?php echo t('Inactive'); ?></option>
                        </select>
                    </label>
                </div>

                <div class="card-hr"><?php echo t('Location Rules'); ?></div>
                <p class="t-muted fs-13"><?php echo t('Show this field group when any of these rule groups match (rules within a group must all match).'); ?></p>

                <?php echo view('form/builder', ['locations' => $locations]); ?>

                <div class="card-hr"><?php echo t('Fields'); ?></div>

                <div class="dg g-3">
                    <div class="field-groups__field card p-4 dg g-3" u-each="(field, i) in fields">
                        <div class="df g-2 aic">
                            <select class="field" u-prop="field.type" u-select>
                                <?php foreach ($fieldTypes as $group) : ?>
                                    <optgroup label="<?php echo $group['label']; ?>">
                                        <?php foreach ($group['options'] as $type => $option) : ?>
                                            <option value="<?php echo $type; ?>"><?php echo $option['label']; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <input class="field" type="text" u-prop="field.label" placeholder="<?php echo t('Field label'); ?>" @input="syncName(field)">
                            <input class="field" type="text" u-prop="field.name" placeholder="<?php echo t('field_name'); ?>">
                            <button type="button" class="btn btn--icon btn--sm" @click="moveField(i, -1)" :disabled="i === 0"><i class="ph ph-arrow-up"></i></button>
                            <button type="button" class="btn btn--icon btn--sm" @click="moveField(i, 1)" :disabled="i === fields.length - 1"><i class="ph ph-arrow-down"></i></button>
                            <button type="button" class="btn btn--icon btn--sm t-red" @click="removeField(i)"><i class="ph ph-trash-simple"></i></button>
                        </div>
                        <div class="df g-2 aic">
                            <label class="df g-1 aic fs-13"><input type="checkbox" u-prop="field.required"> <?php echo t('Required'); ?></label>
                            <input class="field" type="text" u-prop="field.instruction" placeholder="<?php echo t('Instructions'); ?>">
                        </div>
                        <input class="field" type="text" u-prop="field.placeholder" u-show="isTextLike(field.type)" placeholder="<?php echo t('Placeholder'); ?>">
                        <div class="df g-2" u-show="field.type === 'number'">
                            <input class="field" type="number" u-prop="field.min" placeholder="<?php echo t('Min'); ?>">
                            <input class="field" type="number" u-prop="field.max" placeholder="<?php echo t('Max'); ?>">
                        </div>
                        <input class="field" type="number" u-prop="field.rows" u-show="field.type === 'textarea'" placeholder="<?php echo t('Rows'); ?>">
                        <textarea class="field" u-prop="field.options" u-show="isChoice(field.type)" rows="3" placeholder="<?php echo t("one per line, e.g.\nred:Red\nblue:Blue"); ?>"></textarea>
                    </div>
                </div>

                <button type="button" class="btn btn--sm btn--outline" @click="addField('text')"><i class="ph ph-plus"></i> <?php echo t('Add field'); ?></button>

                <div class="df g-2 mt-4">
                    <button type="submit" class="btn btn--primary"><?php echo t('Save Field Group'); ?></button>
                    <?php if ($current) : ?>
                        <button type="button" class="btn btn--outline t-red" @click="$ajax.post('field-groups/delete', {id})"><?php echo t('Delete'); ?></button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
