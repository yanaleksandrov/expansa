<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use Expansa\Support\Arr;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Site defaults for every outgoing email, applied by the Mail setup callback before the "mailer" hook:
 * the SMTP server, the sender and the DKIM signature from the `mail` option, set on the Mail tab
 * of the settings. Without a server the mail goes through PHP mail().
 */
final class Mailer
{
    /**
     * Fields of the option encrypted by Secrets; the form never shows them back.
     */
    private const array SECRETS = ['password', 'dkim.private', 'dkim.passphrase'];

    /**
     * Apply the defaults to a PHPMailer about to send.
     *
     * @param PHPMailer $mailer
     * @return PHPMailer
     */
    public static function setup(PHPMailer $mailer): PHPMailer
    {
        $mail = (array) Option::get('mail', []);
        $name = (string) ($mail['from_name'] ?? '') ?: (string) Option::get('site.name', '') ?: 'Expansa';

        if (! empty($mail['host'])) {
            $mailer->isSMTP();
            $mailer->Host        = (string) $mail['host'];
            $mailer->Port        = (int) ($mail['port'] ?? 465) ?: 465;
            $mailer->SMTPAuth    = ($mail['username'] ?? '') !== '';
            $mailer->Username    = (string) ($mail['username'] ?? '');
            $mailer->Password    = Secrets::decrypt((string) ($mail['password'] ?? ''));
            $mailer->SMTPSecure  = match ($mail['encryption'] ?? 'ssl') {
                'tls'   => PHPMailer::ENCRYPTION_STARTTLS,
                'none'  => '',
                default => PHPMailer::ENCRYPTION_SMTPS,
            };
            $mailer->SMTPAutoTLS = ($mail['encryption'] ?? 'ssl') !== 'none';
        }

        if (! empty($mail['from'])) {
            $mailer->setFrom((string) $mail['from'], $name);
        } elseif ($mailer->From === 'root@localhost') {
            // PHPMailer's root@localhost is rejected by mail servers
            $mailer->setFrom('no-reply@' . self::host(), $name);
        }

        self::sign($mailer, (array) ($mail['dkim'] ?? []));

        return $mailer;
    }

    /**
     * Turn the submitted Mail tab into the option: empty secrets keep the saved ones unless the server
     * or the DKIM selector is cleared, the port is a number and the encryption one of ssl, tls and none.
     *
     * @param array<string, mixed> $mail Form values of the `mail` option.
     * @return array<string, mixed>
     */
    public static function normalize(array $mail): array
    {
        $mail = Secrets::keep($mail, (array) Option::get('mail', []), self::SECRETS);

        // clearing the server or the selector is the way to drop the secrets
        if (trim((string) ($mail['host'] ?? '')) === '') {
            $mail['password'] = '';
        }
        if (trim((string) Arr::get($mail, 'dkim.selector', '')) === '') {
            Arr::set($mail, 'dkim.private', '');
            Arr::set($mail, 'dkim.passphrase', '');
        }

        $mail['host']       = trim((string) ($mail['host'] ?? ''));
        $mail['port']       = (int) ($mail['port'] ?? 465) ?: 465;
        $mail['encryption'] = in_array($mail['encryption'] ?? '', ['ssl', 'tls', 'none'], true) ? $mail['encryption'] : 'ssl';

        return $mail;
    }

    /**
     * Sign with DKIM when the settings have a domain, a selector and a private key.
     *
     * @param PHPMailer            $mailer
     * @param array<string, mixed> $dkim   `domain`, `selector`, `private` (PEM), `passphrase`.
     * @return void
     */
    private static function sign(PHPMailer $mailer, array $dkim): void
    {
        $key = Secrets::decrypt((string) ($dkim['private'] ?? ''));
        if ($key === '' || empty($dkim['selector'])) {
            return;
        }

        $mailer->DKIM_domain           = (string) ($dkim['domain'] ?? '') ?: self::host();
        $mailer->DKIM_private_string   = $key;
        $mailer->DKIM_selector         = (string) $dkim['selector'];
        $mailer->DKIM_passphrase       = Secrets::decrypt((string) ($dkim['passphrase'] ?? ''));
        $mailer->DKIM_identity         = $mailer->From;
        $mailer->DKIM_copyHeaderFields = false;
        $mailer->DKIM_extraHeaders     = ['List-Unsubscribe', 'List-Help'];
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
