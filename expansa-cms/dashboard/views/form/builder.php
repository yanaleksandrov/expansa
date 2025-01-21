<?php

use app\View;
use Expansa\I18n;

/**
 * Query builder
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/builder.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="builder">
	<div class="builder-wrapper">
		<div class="builder-header">
			<?php
			View::print(
				'views/form/select',
				[
					'type'        => 'select',
					'name'        => 'type',
					'label'       => I18n::_t( 'Type' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'name'     => 'type',
						'required' => 1,
					],
					'options'     => [
						'type'     => I18n::_t( 'Post Type' ),
						'template' => I18n::_t( 'Post Template' ),
						'status'   => I18n::_t( 'Post Status' ),
						'format'   => I18n::_t( 'Post Format' ),
						'category' => I18n::_t( 'Post Category' ),
						'taxonomy' => I18n::_t( 'Post Taxonomy' ),
						'post'     => I18n::_t( 'Post' ),
					],
				],
			);
			View::print(
				'views/form/input',
				[
					'type'        => 'text',
					'name'        => 'label',
					'label'       => I18n::_t( 'Label' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'name'     => 'label',
						'required' => 1,
					],
				],
			);
			View::print(
				'views/form/input',
				[
					'type'        => 'text',
					'name'        => 'name',
					'label'       => I18n::_t( 'Name' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => I18n::_t( 'Single word, no spaces. Underscores and dashes allowed' ),
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'name'     => 'name',
						'required' => 1,
					],
				],
			);
			View::print(
				'views/form/input',
				[
					'type'        => 'text',
					'name'        => 'default',
					'label'       => I18n::_t( 'Default value' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'name'     => 'default',
						'required' => 1,
					],
				],
			);
			?>
		</div>
		<template x-for="(group, key) in groups">
			<div class="builder-group" data-or="<?php I18n::t_attr( 'or' ); ?>">
				<template x-for="(rule, i) in group.rules">
					<div class="builder__rules">
						<div class="dg g-1">
							<select class="select" :name="`group.rules[${i}][location]`">
								<optgroup label="Post">
									<option value="post_type">Post Type</option>
									<option value="post_template">Post Template</option>
									<option value="post_status">Post Status</option>
									<option value="post_format">Post Format</option>
									<option value="post_category">Post Category</option>
									<option value="post_taxonomy">Post Taxonomy</option>
									<option value="post">Post</option>
								</optgroup>
							</select>
						</div>
						<div class="dg g-1">
							<select class="select" :name="`group.rules[${i}][operator]`">
								<option value="===">is equal to</option>
								<option value="!=">is not equal to</option>
							</select>
						</div>
						<div class="dg g-1">
							<select class="select" :name="`group.rules[${i}][value]`">
								<option value="subscriber">Subscriber</option>
								<option value="contributor">Contributor</option>
								<option value="author">Author</option>
								<option value="editor">Editor</option>
								<option value="administrator">Administrator</option>
							</select>
						</div>
						<div class="dg g-1" x-show="group.rules.length > 1">
							<button type="button" class="btn btn--icon t-red" @click="removeRule(key,i)"><i class="ph ph-trash-simple"></i></button>
						</div>
					</div>
				</template>
				<div class="builder__buttons">
					<button type="button" class="btn btn--sm t-red" @click="removeGroup(key)" x-show="groups.length > 1"><i class="ph ph-trash-simple"></i> <?php I18n::t( 'Remove Group' ); ?></button>
					<button type="button" class="btn btn--sm t-purple ml-auto" @click="addRule(key)"><i class="ph ph-plus"></i> <?php I18n::t( 'add rule' ); ?></button>
				</div>
			</div>
		</template>
		<div class="builder__buttons mt-2">
			<button class="btn btn--sm btn--outline" type="button" @click="addGroup"><?php I18n::t( 'Add Group' ); ?></button>
			<button class="btn btn--sm btn--primary" type="submit"><i class="ph ph-floppy-disk"></i> <?php I18n::t( 'Save' ); ?></button>
		</div>
	</div>
</div>
