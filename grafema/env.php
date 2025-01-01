<?php
/**
 * Constants for database name, user, password, host, prefix & charset.
 *
 * Indexes have a maximum size of 767 bytes. Historically, we haven't had to worry about this.
 * Utf8mb4 uses 4 bytes for each character. This means that an index that used to have room for
 * floor(767/3) = 255 characters now only has room for floor(767/4) = 191 characters.
 *
 * @since 2025.1
 */
const GRFM_DB_NAME             = 'alexandrov_cms';
const GRFM_DB_USERNAME         = 'alexandrov_cms';
const GRFM_DB_PASSWORD         = 'Wf9zh5BT';
const GRFM_DB_HOST             = 'localhost';
const GRFM_DB_PREFIX           = 'grafema_';
const GRFM_DB_TYPE             = 'mysql';
const GRFM_DB_CHARSET          = 'utf8mb4';
const GRFM_DB_COLLATE          = '';
const GRFM_DB_MAX_INDEX_LENGTH = 191;

/**
 * Constants for paths to Grafema directories.
 *
 * @since 2025.1
 */
const GRFM_CORE      = __DIR__ . '/core/';
const GRFM_DASHBOARD = __DIR__ . '/dashboard/';
const GRFM_PLUGINS   = __DIR__ . '/plugins/';
const GRFM_THEMES    = __DIR__ . '/themes/';
const GRFM_UPLOADS   = __DIR__ . '/uploads/';
const GRFM_I18N      = __DIR__ . '/i18n/';

/**
 * Authentication unique keys and salts.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2025.1
 */
const GRFM_AUTH_KEY  = 'authkey';
const GRFM_NONCE_KEY = 'noncekey';
const GRFM_HASH_KEY  = 'hashkey';

/**
 * Debug mode.
 *
 * @since 2025.1
 */
const GRFM_DEBUG     = true;
const GRFM_DEBUG_LOG = true;

/**
 * Cron intervals.
 *
 * @since 2025.1
 */
const GRFM_HOUR_IN_SECONDS     = 3600;
const GRFM_HALF_DAY_IN_SECONDS = 43200;
const GRFM_DAY_IN_SECONDS      = 86400;
