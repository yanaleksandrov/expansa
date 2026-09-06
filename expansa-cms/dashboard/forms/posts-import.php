<?php
/**
 * Form for build custom fields
 *
 * @since 2025.1
 */
return \Expansa\Facades\Form::enqueue(
	'posts-import',
	[
		'class'           => 'card card-border',
		'@submit.prevent' => '$ajax("posts/import").then(response => output = response.output,$wizard.goNext())',
		'u-data'          => '{fields: "", output: ""}',
	],
	[
		[
			'name'     => 'title',
			'type'     => 'custom',
			'callback' => function () {
				?>
				<div class="progress" :style="'--expansa-progress:' + $wizard.progress().progress"></div>
				<div class="p-7 df aic jcsb">
					<span u-text="$wizard.current().title"><?php echo t( 'Upload CSV file' ); ?></span>
					<span class="t-muted">
						step <strong u-text="$wizard.progress().current">1</strong> from <strong u-text="$wizard.progress().total">2</strong>
					</span>
				</div>
				<div class="card-hr"></div>
				<?php
			},
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'          => 'pl-7 pr-7',
				'u-wizard:step'  => 'fields.trim()',
				'u-wizard:title' => t( 'Upload CSV file' ),
			],
			'fields' => [
				[
					'name'        => 'title',
					'type'        => 'header',
					'class'       => 'p-7 t-center',
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
				'class'          => 'pl-7 pr-7',
				'u-cloak'        => true,
				'u-wizard:step'  => 'output.trim()',
				'u-wizard:title' => t( 'Column mapping' ),
			],
			'fields' => [
				[
					'name'        => 'title',
					'type'        => 'header',
					'class'       => 'p-7 t-center',
					'label'       => t( 'Map CSV fields to posts' ),
					'instruction' => t( 'Select fields from your CSV file that you want to map to fields in the posts, or that you want to ignore during import' ),
				],
				[
					'type'     => 'custom',
					'callback' => fn () => '<div class="dg g-6" u-html="fields"></div>',
				],
			],
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'          => 'dg p-7',
				'u-html'         => 'output',
				'u-cloak'        => true,
				'u-wizard:title' => t( 'Import is completed' ),
			],
		],
		[
			'type'     => 'custom',
			'callback' => function () {
				?>
				<!-- buttons -->
				<div class="p-7 df jcsb g-2" u-show="!output.trim()">
					<button type="button" class="btn btn--outline" :disabled="$wizard.cannotGoBack()" u-show="$wizard.isNotLast()" @click="$wizard.goBack()" disabled><?php echo t( 'Back' ); ?></button>
					<button type="button" class="btn btn--primary" :disabled="$wizard.cannotGoNext()" u-show="$wizard.isFirst()" @click="$wizard.goNext()" disabled><?php echo t( 'Continue' ); ?></button>
					<button type="submit" class="btn btn--primary" u-show="$wizard.isStep(1)" u-cloak><?php echo t( 'Run the importer' ); ?></button>
				</div>
				<?php
			},
		],
	]
);