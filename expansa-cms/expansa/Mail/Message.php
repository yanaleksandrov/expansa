<?php

declare(strict_types=1);

namespace Expansa\Mail;

use Closure;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * One email built with a fluent interface over PHPMailer: `Mail::to($email)->subject(...)->message(...)->send()`.
 * The setup callback from Mail\Manager::configure() prepares or replaces the PHPMailer right before sending.
 *
 * @package Expansa\Mail
 */
final class Message
{
    /**
     * Error of the last send(), empty after a successful one.
     */
    public string $error {
        get => $this->mailer->ErrorInfo;
    }

    public function __construct(

        /**
         * Underlying PHPMailer instance.
         */
        private PHPMailer $mailer = new PHPMailer(),

        /**
         * Gets the PHPMailer before sending and returns the one to send with: SMTP settings, a test double.
         */
        private readonly ?Closure $setup = null,
    ) {
        // PHPMailer defaults to ISO-8859-1, which garbles any non-Latin text
        $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;
    }

    /**
     * Add a recipient.
     *
     * @param string $email
     * @return static
     * @throws Exception
     */
    public function to(string $email): static
    {
        $this->mailer->addAddress($email);

        return $this;
    }

    /**
     * Set the sender.
     *
     * @param string $email
     * @return static
     * @throws Exception
     */
    public function from(string $email): static
    {
        $this->mailer->setFrom($email);

        return $this;
    }

    /**
     * Add a reply-to address.
     *
     * @param string $email
     * @return static
     * @throws Exception
     */
    public function replyTo(string $email): static
    {
        $this->mailer->addReplyTo($email);

        return $this;
    }

    public function subject(string $subject): static
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    /**
     * Set the body; one with HTML tags is sent as HTML with a plain-text alternative.
     *
     * @param string $message
     * @return static
     */
    public function message(string $message): static
    {
        $html = $message !== strip_tags($message);

        $this->mailer->isHTML($html);
        $this->mailer->Body    = $message;
        $this->mailer->AltBody = $html ? $this->mailer->html2text($message) : '';

        return $this;
    }

    /**
     * Add custom headers, one `Name: value` per line.
     *
     * @param string $headers
     * @return static
     * @throws Exception
     */
    public function headers(string $headers): static
    {
        foreach (preg_split('/\R/', trim($headers), -1, PREG_SPLIT_NO_EMPTY) as $header) {
            $this->mailer->addCustomHeader($header);
        }

        return $this;
    }

    /**
     * Attach files, missing ones are skipped.
     *
     * @param string[] $attachments Paths.
     * @return static
     * @throws Exception
     */
    public function attach(array $attachments): static
    {
        foreach ($attachments as $attachment) {
            if (is_file($attachment)) {
                $this->mailer->addAttachment($attachment);
            }
        }

        return $this;
    }

    /**
     * Send the email; peer verification is off for self-signed certificates unless the setup callback changes it.
     *
     * @return bool False with the reason in $error.
     * @throws Exception
     */
    public function send(): bool
    {
        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        if ($this->setup !== null) {
            $this->mailer = ($this->setup)($this->mailer);
        }

        return $this->mailer->send();
    }
}
