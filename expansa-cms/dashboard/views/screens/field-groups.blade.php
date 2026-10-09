<?php
/**
 * Custom Fields builder (ACF-style): create field groups made of custom fields, with
 * simple location rules describing where a group applies; the data comes from App\Dashboard\Pages\FieldGroups.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/field-groups.php
 *
 * @var App\Models\FieldGroup[]      $groups
 * @var int                          $groupId
 * @var App\Models\FieldGroup|null   $current
 * @var array<string, array>         $fieldTypes
 * @var array<string, array>         $locations
 * @var array<string, array>         $valueOptions
 * @var array<string, mixed>         $state
 *
 * @package Expansa\Templates
 */

use Expansa\Facades\Json;
use Expansa\Facades\Safe;
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
                    <p class="t-muted fs-13"><?php echo t('No field groups yet'); ?></p>
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
                            <input class="field" type="text" u-prop="field.name" placeholder="field_name">
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

                <button type="button" class="btn btn--sm btn--outline" @click="addField('text')"><i class="ph ph-plus"></i> <?php echo t('Add Field'); ?></button>

                <div class="card-hr"><?php echo t('Location Rules'); ?></div>
                <p class="t-muted fs-13"><?php echo t('Show this field group when any of these rule groups match (rules within a group must all match).'); ?></p>

                <?php echo view('components/form/builder', ['locations' => $locations]); ?>

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
