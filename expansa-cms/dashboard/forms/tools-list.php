<?php
use Expansa\I18n;
use Expansa\Url;
use Expansa\Safe;

/**
 * Form for build tools list.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'tools-list',
	[
		'class' => 'card card-border p-8 mw-600 m-auto',
	],
	[
		[
			'name'        => 'title',
			'type'        => 'header',
			'class'       => 'px-8 t-center',
			'label'       => I18n::_t( 'Tools Page' ),
			'instruction' => I18n::_t( 'Here are additional tools for working with Expansa. To start, select a tool from the list below:' ),
		],
		[
			'name'     => 'title',
			'type'     => 'custom',
			'callback' => function () {
				$tools = [
					[
						'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="40" y1="216" x2="120" y2="136" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="8"/><polyline points="120 200 119.99 136.01 56 136" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="8"/><path d="M160,184h48a8,8,0,0,0,8-8V48a8,8,0,0,0-8-8H80a8,8,0,0,0-8,8V96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="8"/></svg>',
						'image'       => '',
						'title'       => I18n::_t( 'Content Importer' ),
						'link'        => Url::site( 'dashboard/import' ),
						'description' => I18n::_t( 'If you have posts or comments in another system, Expansa can import those into this site.' ),
					],
					[
						'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="136" y1="120" x2="216" y2="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="8"/><polyline points="216 104 215.99 40.01 152 40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="8"/><path d="M184,136v72a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V80a8,8,0,0,1,8-8h72" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="8"/></svg>',
						'image'       => '',
						'title'       => I18n::_t( 'Exporter' ),
						'link'        => Url::site( 'dashboard/export' ),
						'description' => I18n::_t( 'Once you’ve saved the download file, you can use the Import function in another Expansa installation to import the content from this site.' ),
					],
				];

				?>
				<div class="dg g-1 pt-8 pb-4">
					<?php
					foreach ( $tools as $tool ):
						[ $icon, $image, $title, $link, $description ] = Safe::data(
							$tool,
							[
								'icon'        => 'trim',
								'image'       => 'attribute',
								'title'       => 'trim',
								'link'        => 'url',
								'description' => 'trim',
							]
						)->values();
						?>
						<div class="card card-border p-4 df fdr g-4">
							<span>
								<?php echo $icon; ?>
							</span>
							<div class="dg g-1">
								<h6 class="fs-16"><?php echo $title; ?></h6>
								<div class="fs-13 t-muted lh-sm"><?php echo $description; ?></div>
							</div>
							<div class="ml-auto">
								<a href="<?php echo $link; ?>" class="btn btn--primary">Launch</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			},
		],
		[
			'name'        => 'title',
			'type'        => 'header',
			'class'       => 'px-8 t-center',
			'label'       => '',
			'instruction' => I18n::_t(
				'If the tool you need is not in the list, look in the [plugin catalog](:pluginsCatalogLink) to see if such a tool is available.',
				Url::site( 'dashboard/plugins' )
			),
		],
	],
);