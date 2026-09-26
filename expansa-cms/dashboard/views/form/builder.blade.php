<?php

/**
 * Location rule builder markup: groups of rules ORed together, each rule (location/operator/
 * value) ANDed within its group - e.g. "Post Type is Page" AND "User Role is Editor", OR
 * "Post Status is Draft".
 *
 * This is a plain partial, not its own `u-data` component: it renders into whichever
 * `builder`-scoped host embeds it (currently dashboard/views/field-groups.blade.php's Custom
 * Fields group editor), which is expected to already provide `groups` (array of
 * `{rules: [{location, operator, value}]}`) and `valueOptions` (a `location key => {value:
 * label}` lookup) on its own reactive state, plus the addGroup()/removeGroup()/addRule()/
 * removeRule() methods used below.
 *
 * @param array $locations Location key => {label, options} - only used here to render the
 *                          "location" select; `valueOptions` (same options, keyed the same
 *                          way) is what actually drives the reactive "value" select.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/form/builder.php
 *
 * @package Expansa\Templates
 */
if (! defined('EX_PATH')) {
    exit;
}

$locations = $__data['locations'] ?? [];
?>
<div class="builder">
	<div class="builder-wrapper">
		<div class="dg g-4">
			<div class="builder-group" u-each="(group, key) in groups" data-or="<?php echo t(' or '); ?>">
				<div class="dg g-1">
					<div class="builder__rules" u-each="(rule, i) in group.rules">
						<div class="dg g-1">
							<select class="field" u-prop="rule.location" u-select>
								<?php foreach ($locations as $key => $location) : ?>
									<option value="<?php echo $key; ?>"><?php echo $location['label']; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="dg g-1">
							<select class="field" u-prop="rule.operator" u-select>
								<option value="=="><?php echo t('is equal to'); ?></option>
								<option value="!="><?php echo t('is not equal to'); ?></option>
							</select>
						</div>
						<div class="dg g-1">
							<select class="field" u-html="locationValueOptions(rule)" u-prop="rule.value" u-select></select>
						</div>
						<div class="dg g-1" u-show="group.rules.length > 1">
							<button type="button" class="btn btn--icon t-red" @click="removeRule(key, i)"><i class="ph ph-trash-simple"></i></button>
						</div>
					</div>
				</div>
				<div class="builder__buttons">
					<button type="button" class="btn btn--sm t-red" @click="removeGroup(key)" u-show="groups.length > 1"><i class="ph ph-trash-simple"></i> <?php echo t('Remove Group'); ?></button>
					<button type="button" class="btn btn--sm t-purple ml-auto" @click="addRule(key)"><i class="ph ph-plus"></i> <?php echo t('Add rule'); ?></button>
				</div>
			</div>
		</div>
		<div class="builder__buttons mt-2">
			<button class="btn btn--sm btn--outline" type="button" @click="addGroup()"><i class="ph ph-plus"></i> <?php echo t('Add Group'); ?></button>
		</div>
	</div>
</div>
