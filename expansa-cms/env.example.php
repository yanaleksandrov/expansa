<?php
/**
 * Constants for database name, user, password, host, prefix & charset.
 *
 * @since 2025.1
 */
const EX_DB_DRIVER           = 'mysql';
const EX_DB_NAME             = 'db.name';
const EX_DB_USERNAME         = 'db.username';
const EX_DB_PASSWORD         = 'db.password';
const EX_DB_HOST             = 'db.host';
const EX_DB_PREFIX           = 'db.prefix';
const EX_DB_CHARSET          = 'utf8mb4';
const EX_DB_COLLATION        = 'utf8mb4_general_ci';
const EX_DB_PORT             = 21;
// It is disabled by default for better performance.
const EX_DB_LOGGING          = false;
// Error handling strategies when the error has occurred.
// PDO::ERRMODE_SILENT (default) | PDO::ERRMODE_WARNING | PDO::ERRMODE_EXCEPTION
// Read more from https://www.php.net/manual/en/pdo.error-handling.php.
const EX_DB_ERROR_MODE       = PDO::ERRMODE_SILENT;

/**
 * Authentication unique keys and salts.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2025.1
 */
const EX_AUTH_KEY  = 'auth.key';
const EX_NONCE_KEY = 'nonce.key';
const EX_HASH_KEY  = 'hash.key';

/**
 * Debug mode.
 *
 * @since 2025.1
 */
const EX_DEBUG      = true;
const EX_DEBUG_LOG  = true;
const EX_DEBUG_VIEW = EX_DASHBOARD . 'debug.php';

/**
 * DKIM (DomainKeys Identified Mail) settings for signing outgoing emails.
 *
 * @const string EX_DKIM_DOMAIN             The DKIM signing domain, typically matching the 'From' address domain.
 * @const string EX_DKIM_PRIVATE            Path to the private key used for DKIM signing.
 * @const string EX_DKIM_SELECTOR           The DKIM selector used in the DNS record to locate the public key.
 * @const string EX_DKIM_PASSPHRASE         The passphrase for the private key, if applicable.
 * @const string EX_DKIM_IDENTITY           The identity for signing the email. Typically set to the 'From' address.
 * @const bool   EX_DKIM_COPY_HEADER_FIELDS Whether to include signed header fields in the DKIM signature.
 * @const array  EX_DKIM_EXTRA_HEADERS      Optional list of extra headers to sign with the DKIM signature.
 *
 * @since 2025.1
 */
const EX_DKIM_DOMAIN             = 'example.com';
const EX_DKIM_PRIVATE            = 'dkim_private.pem';
const EX_DKIM_SELECTOR           = 'phpmailer';
const EX_DKIM_PASSPHRASE         = '';
const EX_DKIM_IDENTITY           = '';
const EX_DKIM_COPY_HEADER_FIELDS = false;
const EX_DKIM_EXTRA_HEADERS      = ['List-Unsubscribe', 'List-Help'];
