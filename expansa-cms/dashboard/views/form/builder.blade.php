<?php

/**
 * Query builder
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/builder.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="builder">
	<div class="builder-wrapper">
		<div class="builder-header">
			<?php
			echo view(
				'form/select',
				[
					'type'        => 'select',
					'name'        => 'type',
					'label'       => t( 'Type' ),
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
						'type'     => t( 'Post Type' ),
						'template' => t( 'Post Template' ),
						'status'   => t( 'Post Status' ),
						'format'   => t( 'Post Format' ),
						'category' => t( 'Post Category' ),
						'taxonomy' => t( 'Post Taxonomy' ),
						'post'     => t( 'Post' ),
					],
				],
			);
			echo view(
				'form/input',
				[
					'type'        => 'text',
					'name'        => 'label',
					'label'       => t( 'Label' ),
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
			echo view(
				'form/input',
				[
					'type'        => 'text',
					'name'        => 'name',
					'label'       => t( 'Name' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => t( 'Single word, no spaces. Underscores and dashes allowed' ),
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'name'     => 'name',
						'required' => 1,
					],
				],
			);
			echo view(
				'form/input',
				[
					'type'        => 'text',
					'name'        => 'default',
					'label'       => t( 'Default value' ),
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
			<div class="builder-group" data-or="<?php echo t_attr( 'or' ); ?>">
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
					<button type="button" class="btn btn--sm t-red" @click="removeGroup(key)" x-show="groups.length > 1"><i class="ph ph-trash-simple"></i> <?php echo t( 'Remove Group' ); ?></button>
					<button type="button" class="btn btn--sm t-purple ml-auto" @click="addRule(key)"><i class="ph ph-plus"></i> <?php echo t( 'add rule' ); ?></button>
				</div>
			</div>
		</template>
		<div class="builder__buttons mt-2">
			<button class="btn btn--sm btn--outline" type="button" @click="addGroup"><?php echo t( 'Add Group' ); ?></button>
			<button class="btn btn--sm btn--primary" type="submit"><i class="ph ph-floppy-disk"></i> <?php echo t( 'Save' ); ?></button>
		</div>
	</div>
</div>
