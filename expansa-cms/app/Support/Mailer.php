<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Site defaults for every outgoing email, applied by the Mail setup callback before the "mailer" hook:
 * the sender and the DKIM signature from EX_DKIM.
 */
final class Mailer
{
    /**
     * Apply the defaults to a PHPMailer about to send.
     *
     * @param PHPMailer $mailer
     * @return PHPMailer
     */
    public static function setup(PHPMailer $mailer): PHPMailer
    {
        // PHPMailer's root@localhost is rejected by mail servers
        if ($mailer->From === 'root@localhost') {
            $mailer->setFrom('no-reply@' . self::host(), (string) Option::get('site.name', '') ?: 'Expansa');
        }

        self::sign($mailer);

        return $mailer;
    }

    /**
     * Sign with DKIM when EX_DKIM names an existing private key; an absolute path
     * or one relative to the CMS root.
     *
     * @param PHPMailer $mailer
     * @return void
     */
    private static function sign(PHPMailer $mailer): void
    {
        $dkim = defined('EX_DKIM') ? (array) EX_DKIM : [];
        $key  = (string) ($dkim['private'] ?? '');
        $key  = is_file($key) ? $key : EX_PATH . ltrim($key, '/\\');

        if ($key === EX_PATH || ! is_file($key) || empty($dkim['selector'])) {
            return;
        }

        $mailer->DKIM_domain           = (string) ($dkim['domain'] ?? '') ?: self::host();
        $mailer->DKIM_private          = $key;
        $mailer->DKIM_selector         = (string) $dkim['selector'];
        $mailer->DKIM_passphrase       = (string) ($dkim['passphrase'] ?? '');
        $mailer->DKIM_identity         = (string) ($dkim['identity'] ?? '') ?: $mailer->From;
        $mailer->DKIM_copyHeaderFields = (bool) ($dkim['copyHeaderFields'] ?? false);
        $mailer->DKIM_extraHeaders     = (array) ($dkim['extraHeaders'] ?? []);
    }

    /**
     * Site host without "www.".
     *
     * @return string
     */
    private static function host(): string
    {
        return preg_replace('/^www\./', '', (string) parse_url(url(), PHP_URL_HOST));
    }
}
