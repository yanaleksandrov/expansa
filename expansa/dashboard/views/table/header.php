<?php

use app\View;
use Expansa\I18n;
use Expansa\Safe;
use Expansa\Url;

/**
 * Table header
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/header.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $title, $badge, $show, $content, $uploader, $filter, $actions, $search, $translation ] = Safe::data(
	$args ?? [],
	[
		'title'       => 'trim',
		'badge'       => 'trim',
		'show'        => 'bool:true',
		'content'     => 'trim',
		'uploader'    => 'bool:false',
		'filter'      => 'bool:false',
		'actions'     => 'bool:false',
		'search'      => 'bool:false',
		'translation' => 'bool:false',
	]
)->values();
?>
<!-- table head start -->
<div class="table__header">
    <div class="mw df fww aic jcsb g-3 py-5 px-7 md:p-5">
		<?php if ( $title ) : ?>
            <h4><?php echo $title; ?>
	            <?php $badge && print( '<span class="badge">' . $badge . '</span>' ); ?>
            </h4>
		<?php endif; ?>
	    <div class="df aic g-1">
		    <div class="df aic g-1" x-show="!bulk">
			    <?php if ( $filter ) : ?>
				    <div class="df aic g-1">
					    <button class="btn btn--sm btn--outline" type="reset" form="expansa-items-filter" @click="showFilter = !showFilter" :class="showFilter && 't-red'" :title="showFilter ? '<?php I18n::t_attr( 'Reset Filter' ); ?>' : '<?php I18n::t( 'Filter' ); ?>'">
						    <i class="ph ph-funnel" :class="showFilter ? 'ph-funnel-x' : 'ph-funnel'"></i>
						    <span x-text="showFilter ? '<?php I18n::t_attr( 'Reset' ); ?>' : '<?php I18n::t_attr( 'Filter' ); ?>'"><?php I18n::t( 'Filter' ); ?></span>
					    </button>
					    <?php
					    View::print(
						    'views/form/number',
						    [
							    'type'        => 'number',
							    'name'        => 'page',
							    'label'       => '',
							    'class'       => 'field field--sm field--outline',
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
								    'min'   => 0,
								    'value' => 3,
							    ],
						    ]
					    );
					    ?>
				    </div>
			    <?php endif; ?>
			    <?php if ( $search ) : ?>
				    <div class="df aic g-1">
					    <?php
					    View::print(
						    'views/form/input',
						    [
							    'type'        => 'search',
							    'name'        => 's',
							    'label'       => '',
							    'class'       => 'field field--sm field--outline',
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
								    'type'        => 'search',
								    'name'        => 's',
								    'placeholder' => I18n::_t( 'Search plugins' ),
							    ],
						    ]
					    );
					    ?>
				    </div>
			    <?php endif; ?>
			    <?php if ( $uploader ) : ?>
				    <div class="df aic g-1">
					    <button class="btn btn--sm btn--outline" @click="$dialog.open('tmpl-media-uploader', uploaderDialog)">
						    <i class="ph ph-upload-simple"></i> <?php I18n::t( 'Add new file' ); ?>
					    </button>
				    </div>
			    <?php endif; ?>
			    <?php if ( $translation ) : ?>
				    <div class="df aic g-2">
					    <div class="df aic g-1">
						    <svg width="16" height="16"><use xlink:href="<?php echo Url::dashboard( '/assets/sprites/flags.svg#us' ); ?>"></use></svg>
						    English
					    </div>
					    <span class="badge badge--round badge--icon badge--lg"><i class="ph ph-arrows-left-right"></i></span>
					    <?php
					    View::print(
						    'views/form/select',
						    [
							    'type'        => 'select',
							    'name'        => 'language',
							    'label'       => '',
							    'class'       => 'field field--sm field--outline',
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
								    'x-select' => '',
								    'name'     => 'language',
							    ],
							    'options'     => I18n::getLanguagesOptions(),
						    ]
					    );

					    View::print(
						    'views/form/select',
						    [
							    'type'        => 'select',
							    'name'        => 'project',
							    'label'       => '',
							    'class'       => 'field field--sm field--outline',
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
								    'name'         => 'project',
								    'x-model.fill' => 'project',
								    'x-select'     => '',
							    ],
							    'options'     => [
								    'core' => [
									    'label'   => I18n::_t( 'Core' ),
									    'options' => [
										    'core' => [
											    'content'     => I18n::_t( 'Expansa Core' ),
											    'description' => I18n::_t( 'completion :percent\%', 0 ),
										    ],
									    ],
								    ],
								    'plugins' => [
									    'label'   => I18n::_t( 'Plugins' ),
									    'options' => array_reduce( Expansa\Plugins::get(), function( $carry, Expansa\Plugin $plugin ) {
										    $carry[ $plugin->id ] = [
											    'content'     => $plugin->name,
											    'description' => I18n::_t( 'completion :percent%', 0 ),
										    ];
										    return $carry;
									    }, [] ),
								    ],
								    'themes' => [
									    'label'   => I18n::_t( 'Themes' ),
									    'options' => array_reduce( Expansa\Themes::get(), function( $carry, Expansa\Plugin $theme ) {
										    $carry[ $theme->id ] = [
											    'content'     => $theme->name,
											    'description' => I18n::_t( 'completion :percent%', 0 ),
										    ];
										    return $carry;
									    }, [] ),
								    ],
							    ],
						    ]
					    );
					    ?>
					    <button type="button" class="btn btn--sm btn--outline" @click="$ajax('translations/get', {project}).then(data => items = data.items)"><i class="ph ph-scan"></i> <?php I18n::t( 'Scan' ); ?></button>
				    </div>
			    <?php endif; ?>
		    </div>

		    <?php if ( $actions ) : ?>
			    <div class="df aic g-1" x-show="bulk" x-cloak>
				    <?php Dashboard\Form::print( EX_DASHBOARD . 'forms/posts-actions.php' ); ?>
				    <button type="button" class="btn btn--sm t-red" x-bind="reset"><i class="ph ph-trash"></i> <?php I18n::t( 'Reset' ); ?></button>
			    </div>
		    <?php endif; ?>
	    </div>
    </div>
	<?php //Dashboard\Form::print( 'items-filter' ); ?>
    <?php $content && print( $content . PHP_EOL ); ?>
</div>
