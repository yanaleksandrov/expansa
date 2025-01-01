<?php
use Grafema\Asset;
use Grafema\I18n;
use Grafema\Is;
use Grafema\Tree;
use Grafema\Hook;

/**
 * Boilerplate plugin.
 *
 * @since 2025.1
 */
return new class extends Grafema\Plugin {

	public function __construct() {
		$this
			->setName( 'Toolkit' )
			->setVersion( '2024.9' )
			->setAuthor( 'Grafema Team' )
			->setDescription( I18n::_t( 'The developer tools panel for Grafema' ) );
	}

	public static function launch()
	{
		if ( ! Is::dashboard() ) {
			return;
		}

		Asset::enqueue( 'toolkit-main', '/plugins/toolkit/assets/css/main.css' );

		Hook::add( 'grafema_view_part', function( $filepath ) {
			if ( $filepath === GRFM_DASHBOARD . 'views/fields-builder.php' ) {
				$filepath = __DIR__ . '/views/fields-builder.php';
			}
			if ( $filepath === GRFM_DASHBOARD . 'views/forms-builder.php' ) {
				$filepath = __DIR__ . '/views/forms-builder.php';
			}
			return $filepath;
		} );

		Tree::attach( 'dashboard-main-menu', fn ( $tree ) => $tree->addItems(
			[
				[
					'id'           => 'toolkit',
					'url'          => 'forms-builder',
					'title'        => I18n::_t( 'Dev toolkit' ),
					'capabilities' => ['manage_options'],
					'icon'         => 'ph ph-brackets-curly',
					'position'     => 800,
				],
				[
					'id'           => 'fields-builder',
					'url'          => 'fields-builder',
					'title'        => I18n::_t( 'Fields builder' ),
					'capabilities' => ['manage_options'],
					'icon'         => '',
					'position'     => 0,
					'parent_id'    => 'toolkit',
				],
				[
					'id'           => 'forms-builder',
					'url'          => 'forms-builder',
					'title'        => I18n::_t( 'Forms builder' ),
					'capabilities' => ['manage_options'],
					'icon'         => '',
					'position'     => 0,
					'parent_id'    => 'toolkit',
				],
			]
		) );

		/*
		 * Sign In form
		 *
		 * @since 2025.1
		 */
		Dashboard\Form::enqueue(
			'builder/fields',
			[
				'class'  => 'dg p-7 g-7',
				'x-data' => "{type: '', tab:'general'}",
			],
			[
				[
					'name'    => 'manage',
					'type'    => 'group',
					'label'   => I18n::_t( 'Managing fields' ),
					'class'   => '',
					'columns' => '',
					'fields'  => [
						[
							'name'        => 'general',
							'type'        => 'tab',
							'label'       => I18n::_t( 'General' ),
							'caption'     => '',
							'description' => '',
							'icon'        => 'ph ph-cube',
							'fields'      => [
								[
									'type'        => 'select',
									'label'       => I18n::_t( 'Field Type' ),
									'name'        => 'type',
									'value'       => '',
									'placeholder' => '',
									'class'       => 'df aic fs-12 t-muted',
									'reset'       => 0,
									'required'    => 0,
									'before'      => '',
									'after'       => '',
									'tooltip'     => '',
									'instruction' => '',
									'attributes'  => [],
									'options' => [
										'main' => [
											'label'   => I18n::_t( 'Basic' ),
											'options' => [
												'input' => [
													'content' => I18n::_t( 'Text' ),
													'icon'    => 'ph ph-text-t',
												],
												'textarea' => [
													'content' => I18n::_t( 'Text Area' ),
													'icon'    => 'ph ph-textbox',
												],
												'number' => [
													'content' => I18n::_t( 'Number' ),
													'icon'    => 'ph ph-hash',
												],
												'range' => [
													'content' => I18n::_t( 'Range (TODO)' ),
													'icon'    => 'ph ph-hash',
												],
												'email' => [
													'content' => I18n::_t( 'Email (TODO)' ),
													'icon'    => 'ph ph-at',
												],
												'url' => [
													'content' => I18n::_t( 'URL (TODO)' ),
													'icon'    => 'ph ph-link',
												],
												'password' => [
													'content' => I18n::_t( 'Password' ),
													'icon'    => 'ph ph-password',
												],
												'submit' => [
													'content' => I18n::_t( 'Submit Button' ),
													'icon'    => 'ph ph-paper-plane-tilt',
												],
												'date' => [
													'content' => I18n::_t( 'Date Picker (TODO)' ),
													'icon'    => 'ph ph-calendar',
												],
												'date_time' => [
													'content' => I18n::_t( 'Date & Time Picker (TODO)' ),
													'icon'    => 'ph ph-calendar',
												],
												'time' => [
													'content' => I18n::_t( 'Time Picker (TODO)' ),
													'icon'    => 'ph ph-clock-countdown',
												],
												'color' => [
													'content' => I18n::_t( 'Color Picker (TODO)' ),
													'icon'    => 'ph ph-swatches',
												],
											],
										],
										'content' => [
											'label'   => I18n::_t( 'Content' ),
											'options' => [
												'image' => [
													'content' => I18n::_t( 'Image' ),
													'icon'    => 'ph ph-image-square',
												],
												'media' => [
													'content' => I18n::_t( 'Media' ), // TODO: gallery instead
													'icon'    => 'ph ph-images-square',
												],
												'editor' => [
													'content' => I18n::_t( 'WYSIWYG editor (TODO)' ),
													'icon'    => 'ph ph-image-square',
												],
												'uploader' => [
													'content' => I18n::_t( 'Uploader' ),
													'icon'    => 'ph ph-paperclip',
												],
												'progress' => [
													'content' => I18n::_t( 'Progress' ),
													'icon'    => 'ph ph-spinner-gap',
												],
											],
										],
										'choice' => [
											'label'   => I18n::_t( 'Choice' ),
											'options' => [
												'select' => [
													'content' => I18n::_t( 'Select' ),
													'icon'    => 'ph ph-list-checks',
												],
												'checkbox' => [
													'content' => I18n::_t( 'Checkbox' ),
													'icon'    => 'ph ph-toggle-left',
												],
												'radio' => [
													'content' => I18n::_t( 'Radio Button' ),
													'icon'    => 'ph ph-radio-button',
												],
											],
										],
										'relations' => [
											'label'   => I18n::_t( 'Relations' ),
											'options' => [
												'link' => [
													'content' => I18n::_t( 'Link (TODO)' ),
													'icon'    => 'ph ph-link-simple',
												],
												'post' => [
													'content' => I18n::_t( 'Post Object (TODO)' ),
													'icon'    => 'ph ph-note',
												],
												'user' => [
													'content' => I18n::_t( 'User (TODO)' ),
													'icon'    => 'ph ph-user',
												],
											],
										],
										'additional' => [
											'label'   => I18n::_t( 'Layout' ),
											'options' => [
												'details' => [
													'content' => I18n::_t( 'Details' ), // TODO: rename context menu
													'icon'    => 'ph ph-dots-three-outline-vertical',
												],
												'divider' => [
													'content' => I18n::_t( 'Divider' ),
													'icon'    => 'ph ph-arrows-out-line-vertical',
												],
												'step' => [
													'content' => I18n::_t( 'Step' ),
													'icon'    => 'ph ph-steps',
												],
												'tab' => [
													'content' => I18n::_t( 'Tab' ),
													'icon'    => 'ph ph-tabs',
												],
												'repeater' => [
													'content' => I18n::_t( 'Repeater (TODO)' ),
													'icon'    => 'ph ph-infinity',
												],
											],
										],
									],
									'conditions' => [],
								],
								[
									'name'        => 'label',
									'type'        => 'text',
									'label'       => I18n::_t( 'Field Label' ),
									'label_class' => 'df aic fs-12 t-muted',
									'before'      => '',
									'tooltip'     => '',
									'instruction' => I18n::_t( 'This is the name which will appear on the EDIT page' ),
									'attributes'  => [
										'value'       => 'Title',
										'placeholder' => I18n::_t( 'Field label' ),
									],
								],
								[
									'name'        => 'name',
									'type'        => 'text',
									'label'       => I18n::_t( 'Field Name' ),
									'label_class' => 'df aic fs-12 t-muted',
									'before'      => '',
									'tooltip'     => '',
									'instruction' => I18n::_t( 'Single word, no spaces. Underscores and dashes allowed' ),
									'attributes'  => [
										'value'       => '',
										'placeholder' => I18n::_t( 'Field label' ),
									],
								],
								[
									'name'        => 'value',
									'type'        => 'text',
									'label'       => I18n::_t( 'Default Value' ),
									'label_class' => 'df aic fs-12 t-muted',
									'before'      => '',
									'tooltip'     => '',
									'instruction' => I18n::_t( 'Appears when creating a new post' ),
									'attributes'  => [
										'value' => '',
									],
								],
								[
									'name'        => 'options',
									'type'        => 'textarea',
									'label'       => I18n::_t( 'Options' ),
									'class'       => 'df aic fs-12 t-muted',
									'value'       => 'red:Red',
									'before'      => '',
									'after'       => '',
									'tooltip'     => '',
									'limits'      => 0,
									'required'    => 0,
									'placeholder' => I18n::_t( 'Placeholder text' ),
									'instruction' => I18n::_t( 'Enter each choice on a new line. You must specify both the value and the label as follows: red:Red' ),
									'conditions'  => [
										[
											'field'    => 'type',
											'operator' => 'contains', // value contains
											'value'    => ['select'],
										],
									],
								],
							],
						],
						[
							'name'        => 'validation',
							'type'        => 'tab',
							'label'       => I18n::_t( 'Validation' ),
							'caption'     => '',
							'description' => '',
							'icon'        => 'ph ph-shield-check',
							'fields'      => [
								[
									'type'        => 'checkbox',
									'label'       => I18n::_t( 'Required' ),
									'name'        => 'required',
									'value'       => '',
									'placeholder' => '',
									'class'       => '',
									'reset'       => 0,
									'required'    => 0,
									'before'      => '',
									'after'       => '',
									'tooltip'     => '',
									'instruction' => I18n::_t( 'The form will not be saved if it is not filled in' ),
									'attributes'  => [],
									'conditions'  => [],
									'options'     => [],
								],
							],
						],
						[
							'name'        => 'presentation',
							'type'        => 'tab',
							'label'       => I18n::_t( 'Presentation' ),
							'caption'     => '',
							'description' => '',
							'icon'        => 'ph ph-presentation-chart',
							'fields'      => [
								[
									'name'        => 'label_class',
									'type'        => 'text',
									'label'       => I18n::_t( 'Label class' ),
									'label_class' => 'df aic fs-12 t-muted',
									'before'      => '',
									'tooltip'     => '',
									'instruction' => '',
									'attributes'  => [
										'placeholder' => I18n::_t( 'e.g. df aic fs-12 t-muted' ),
									],
								],
								[
									'name'        => 'before',
									'type'        => 'text',
									'label'       => I18n::_t( 'Before content' ),
									'label_class' => 'df aic fs-12 t-muted',
									'before'      => '',
									'tooltip'     => '',
									'instruction' => '',
									'attributes'  => [
										'placeholder' => I18n::_t( 'e.g. <i class="ph ph-bug"></i>' ),
									],
								],
								[
									'name'        => 'after',
									'type'        => 'text',
									'label'       => I18n::_t( 'After content' ),
									'label_class' => 'df aic fs-12 t-muted',
									'before'      => '',
									'tooltip'     => '',
									'instruction' => '',
									'attributes'  => [
										'placeholder' => I18n::_t( 'e.g. Mb' ),
									],
								],
								[
									'name'        => 'reset',
									'type'        => 'select',
									'label'       => I18n::_t( 'Show reset button' ),
									'label_class' => 'df aic fs-12 t-muted',
									'value'       => '',
									'reset'       => false,
									'attributes'  => [],
									'options' => [
										'yes' => I18n::_t( 'Yes' ),
										'no'  => I18n::_t( 'No' ),
									],
								],
								[
									'name'        => 'copy',
									'type'        => 'select',
									'label'       => I18n::_t( 'Show copy button' ),
									'label_class' => 'df aic fs-12 t-muted',
									'value'       => '',
									'reset'       => false,
									'attributes'  => [],
									'options' => [
										'yes' => I18n::_t( 'Yes' ),
										'no'  => I18n::_t( 'No' ),
									],
								],
								[
									'name'        => 'description',
									'type'        => 'textarea',
									'value'       => '',
									'default'     => '',
									'label'       => I18n::_t( 'Description' ),
									'class'       => 'df aic fs-12 t-muted',
									'reset'       => false,
									'before'      => '',
									'after'       => '',
									'tooltip'     => '',
									'placeholder' => I18n::_t( 'e.g. Mb' ),
									'instruction' => I18n::_t( 'Use this field to output instructions or additional explanations' ),
									'attributes'  => [],
									'conditions'  => [],
								],
							],
						],
					],
				],
			]
		);
	}

	public static function activate()
	{
		// do something when plugin is activated
	}

	public static function deactivate()
	{
		// do something when plugin is deactivated
	}

	public static function install()
	{
		// do something when plugin is installed
	}

	public static function uninstall()
	{
		// do something when plugin is uninstalled
	}
};
