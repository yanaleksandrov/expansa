<?php
/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'posts-import',
	[
		'class'           => 'card card-border',
		'@submit.prevent' => '$ajax("posts/import").then(response => output = response.output,$wizard.goNext())',
		'x-data'          => '{fields: "", output: ""}',
	],
	[
		[
			'name'     => 'title',
			'type'     => 'custom',
			'callback' => function () {
				?>
				<div class="progress" :style="'--expansa-progress:' + $wizard.progress().progress"></div>
				<div class="p-8 pt-7 pb-7 df aic jcsb">
					<span x-text="$wizard.current().title"><?php echo t( 'Upload CSV file' ); ?></span>
					<span class="t-muted">
						step <strong x-text="$wizard.progress().current">1</strong> from <strong x-text="$wizard.progress().total">2</strong>
					</span>
				</div>
				<div class="card-hr"></div>
				<?php
			},
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'          => 'pl-8 pr-8',
				'x-wizard:step'  => 'fields.trim()',
				'x-wizard:title' => t( 'Upload CSV file' ),
			],
			'fields' => [
				[
					'name'        => 'title',
					'type'        => 'header',
					'class'       => 'p-8 t-center',
					'label'       => t( 'Import posts from a CSV file' ),
					'instruction' => t( 'This tool allows you to import (or merge) posts data to your website from a CSV or TXT file. %sDownload%s the file for an example or choose a file from your computer:', '<a href="/dashboard/assets/files/example-posts.csv" download>', '</a>' ),
				],
				[
					'type'        => 'uploader',
					'name'        => 'uploader',
					'label'       => '',
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => t( 'Click to upload or drag & drop' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'accept'  => '.csv,.txt',
						'@change' => '$ajax("files/upload").then(response => fields = response.fields,$wizard.goNext())',
					],
				],
			],
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'          => 'pl-8 pr-8',
				'x-cloak'        => true,
				'x-wizard:step'  => 'output.trim()',
				'x-wizard:title' => t( 'Column mapping' ),
			],
			'fields' => [
				[
					'name'        => 'title',
					'type'        => 'header',
					'class'       => 'p-8 t-center',
					'label'       => t( 'Map CSV fields to posts' ),
					'instruction' => t( 'Select fields from your CSV file that you want to map to fields in the posts, or that you want to ignore during import' ),
				],
				[
					'type'     => 'custom',
					'callback' => fn () => '<div class="dg g-6" x-html="fields"></div>',
				],
			],
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'          => 'dg p-8',
				'x-html'         => 'output',
				'x-cloak'        => true,
				'x-wizard:title' => t( 'Import is completed' ),
			],
		],
		[
			'type'     => 'custom',
			'callback' => function () {
				?>
				<!-- buttons -->
				<div class="p-8 df jcsb g-2" x-show="!output.trim()">
					<button type="button" class="btn btn--outline" :disabled="$wizard.cannotGoBack()" x-show="$wizard.isNotLast()" @click="$wizard.goBack()" disabled><?php echo t( 'Back' ); ?></button>
					<button type="button" class="btn btn--primary" :disabled="$wizard.cannotGoNext()" x-show="$wizard.isFirst()" @click="$wizard.goNext()" disabled><?php echo t( 'Continue' ); ?></button>
					<button type="submit" class="btn btn--primary" x-show="$wizard.isStep(1)" x-cloak><?php echo t( 'Run the importer' ); ?></button>
				</div>
				<?php
			},
		],
	]
);