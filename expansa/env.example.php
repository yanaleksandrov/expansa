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
const EX_DB_NAME             = 'db.name';
const EX_DB_USERNAME         = 'db.username';
const EX_DB_PASSWORD         = 'db.password';
const EX_DB_HOST             = 'db.host';
const EX_DB_PREFIX           = 'db.prefix';
const EX_DB_TYPE             = 'mysql';
const EX_DB_CHARSET          = 'utf8mb4';
const EX_DB_COLLATE          = '';
const EX_DB_MAX_INDEX_LENGTH = 191;

/**
 * Constants for paths to Expansa directories.
 *
 * @since 2025.1
 */
const EX_CORE      = __DIR__ . '/core/';
const EX_DASHBOARD = __DIR__ . '/dashboard/';
const EX_PLUGINS   = __DIR__ . '/plugins/';
const EX_THEMES    = __DIR__ . '/themes/';
const EX_UPLOADS   = __DIR__ . '/uploads/';
const EX_I18N      = __DIR__ . '/i18n/';

/**
 * Authentication unique keys and salts.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2025.1
 */
const EX_AUTH_KEY  = 'authkey';
const EX_NONCE_KEY = 'noncekey';
const EX_HASH_KEY  = 'hashkey';

/**
 * Debug mode.
 *
 * @since 2025.1
 */
const EX_DEBUG     = true;
const EX_DEBUG_LOG = true;

/**
 * Cron intervals.
 *
 * @since 2025.1
 */
const EX_HOUR_IN_SECONDS     = 3600;
const EX_HALF_DAY_IN_SECONDS = 43200;
const EX_DAY_IN_SECONDS      = 86400;
