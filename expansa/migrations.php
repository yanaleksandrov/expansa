<?php
use Expansa\I18n;
use Expansa\Post\Type;
use Expansa\User\Roles;

/**
 * Add roles and users.
 *
 * @since 2025.1
 */
Roles::register(
	'admin',
	I18n::_t( 'Administrator' ),
	[
		'read',
		'files_upload',
		'files_edit',
		'files_delete',
		'types_publish',
		'types_edit',
		'types_delete',
		'other_types_publish',
		'other_types_edit',
		'other_types_delete',
		'private_types_publish',
		'private_types_edit',
		'private_types_delete',
		'manage_comments',
		'manage_options',
		'manage_update',
		'manage_import',
		'manage_export',
		'themes_install',
		'themes_switch',
		'themes_delete',
		'plugins_install',
		'plugins_activate',
		'plugins_delete',
		'users_create',
		'users_edit',
		'users_delete',
	]
);

Roles::register(
	'editor',
	I18n::_t( 'Editor' ),
	[
		'read',
		'files_upload',
		'files_edit',
		'files_delete',
		'types_publish',
		'types_edit',
		'types_delete',
		'other_types_publish',
		'other_types_edit',
		'other_types_delete',
		'private_types_publish',
		'private_types_edit',
		'private_types_delete',
		'manage_comments',
	]
);

Roles::register(
	'author',
	I18n::_t( 'Author' ),
	[
		'read',
		'files_upload',
		'files_edit',
		'files_delete',
		'types_publish',
		'types_edit',
		'types_delete',
	]
);

Roles::register(
	'subscriber',
	I18n::_t( 'Subscriber' ),
	[
		'read',
	]
);

/**
 * Set up default post types: "pages", "media" & "api-keys".
 *
 * @since 2025.1
 */
Type::register(
	key: 'pages',
	labelName: I18n::_t( 'Page' ),
	labelNamePlural: I18n::_t( 'Pages' ),
	labelAllItems: I18n::_t( 'All Pages' ),
	labelAdd: I18n::_t( 'Add New' ),
	labelEdit: I18n::_t( 'Edit Page' ),
	labelUpdate: I18n::_t( 'Update Page' ),
	labelView: I18n::_t( 'View Page' ),
	labelSearch: I18n::_t( 'Search Pages' ),
	labelSave: I18n::_t( 'Save Page' ),
	public: true,
	hierarchical: false,
	searchable: true,
	showInMenu: true,
	showInBar: true,
	canExport: true,
	canImport: true,
	capabilities: ['types_edit'],
	menuIcon: 'ph ph-folders',
	menuPosition: 20,
);

Type::register(
	key: 'media',
	labelName: I18n::_t( 'Storage' ),
	labelNamePlural: I18n::_t( 'Storage' ),
	labelAllItems: I18n::_t( 'Library' ),
	labelAdd: I18n::_t( 'Upload' ),
	labelEdit: I18n::_t( 'Edit Media' ),
	labelUpdate: I18n::_t( 'Update Media' ),
	labelView: I18n::_t( 'View Media' ),
	labelSearch: I18n::_t( 'Search Media' ),
	labelSave: I18n::_t( 'Save Media' ),
	public: true,
	hierarchical: false,
	searchable: false,
	showInMenu: true,
	showInBar: false,
	canExport: true,
	canImport: true,
	capabilities: ['types_edit'],
	menuIcon: 'ph ph-dropbox-logo',
	menuPosition: 30,
);

Type::register(
	key: 'api-keys',
	labelName: I18n::_t( 'API Key' ),
	labelNamePlural: I18n::_t( 'API Keys' ),
	labelAllItems: I18n::_t( 'All API Keys' ),
	labelAdd: I18n::_t( 'Add New Key' ),
	labelEdit: I18n::_t( 'Edit Key' ),
	labelUpdate: I18n::_t( 'Update Key' ),
	labelView: I18n::_t( 'View Key' ),
	labelSearch: I18n::_t( 'Search Keys' ),
	labelSave: I18n::_t( 'Save Key' ),
	public: false,
	hierarchical: false,
	searchable: false,
	showInMenu: false,
	showInBar: false,
	canExport: true,
	canImport: true,
	capabilities: ['types_edit'],
	menuIcon: 'ph ph-key',
	menuPosition: 30,
);