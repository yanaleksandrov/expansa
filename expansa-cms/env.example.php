<?php
/**
 * Database connection, passed to Db::configure() as named arguments.
 *
 * @since 2025.1
 */
const EX_DB = [
    'driver'     => 'mysql',
    'database'   => 'db.name',
    'username'   => 'db.username',
    'password'   => 'db.password',
    'host'       => 'db.host',
    'prefix'     => 'db.prefix',
    'charset'    => 'utf8mb4',
    'collation'  => 'utf8mb4_general_ci',
    'port'       => 3306,
    // keeps the connection open between requests served by the same PHP worker
    'persistent' => false,
    // query log; disabled by default for better performance
    'testMode'   => false,
    // PDO::ERRMODE_SILENT (default) | PDO::ERRMODE_WARNING | PDO::ERRMODE_EXCEPTION, see https://www.php.net/manual/en/pdo.error-handling.php
    'error'      => PDO::ERRMODE_SILENT,
];

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
