<?php

declare(strict_types=1);

use App\User;
use App\Post\Type;
use App\User\Roles;

/**
 * Set up current user.
 *
 * @since 2025.1
 */
User::current();

/**
 * Add roles and users.
 *
 * @since 2025.1
 */
Roles::register('admin', t('Administrator'), [
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
]);

Roles::register('editor', t('Editor'), [
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
]);

Roles::register('author', t('Author'), [
    'read',
    'files_upload',
    'files_edit',
    'files_delete',
    'types_publish',
    'types_edit',
    'types_delete',
]);

Roles::register('subscriber', t('Subscriber'), [
    'read',
]);

/**
 * Set up default post types: "pages", "media" & "api-keys".
 *
 * @since 2025.1
 */
Type::register(
    key: 'pages',
    labelName: t('Page'),
    labelNamePlural: t('Pages'),
    labelAllItems: t('All Pages'),
    labelAdd: t('Add New'),
    labelEdit: t('Edit Page'),
    labelUpdate: t('Update Page'),
    labelView: t('View Page'),
    labelSearch: t('Search Pages'),
    labelSave: t('Save Page'),
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
    labelName: t('Storage'),
    labelNamePlural: t('Storage'),
    labelAllItems: t('Library'),
    labelAdd: t('Upload'),
    labelEdit: t('Edit Media'),
    labelUpdate: t('Update Media'),
    labelView: t('View Media'),
    labelSearch: t('Search Media'),
    labelSave: t('Save Media'),
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
    labelName: t('API Key'),
    labelNamePlural: t('API Keys'),
    labelAllItems: t('All API Keys'),
    labelAdd: t('Add New Key'),
    labelEdit: t('Edit Key'),
    labelUpdate: t('Update Key'),
    labelView: t('View Key'),
    labelSearch: t('Search Keys'),
    labelSave: t('Save Key'),
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
