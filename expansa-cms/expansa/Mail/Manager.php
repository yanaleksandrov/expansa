<?php

declare(strict_types=1);

namespace Expansa\Mail;

use Closure;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Creates emails and sends them, the Mail facade instance.
 * Without configure() PHPMailer sends with its defaults: PHP mail().
 *
 * @package Expansa\Mail
 */
final class Manager
{
    /**
     * Gets the PHPMailer of every email before sending and returns the one to send with.
     */
    private ?Closure $setup = null;

    /**
     * Set the setup callback of the emails created from now on.
     *
     * @param Closure|null $setup `fn (PHPMailer $mailer): PHPMailer`, bootstrap.php passes the `mailer` hook.
     * @return void
     */
    public function configure(?Closure $setup = null): void
    {
        $this->setup = $setup;
    }

    /**
     * Start an email to a recipient.
     *
     * @param string $email
     * @return Mailer
     * @throws Exception
     */
    public function to(string $email): Mailer
    {
        return new Mailer(new PHPMailer(), $this->setup)->to($email);
    }

    /**
     * Send an email in one call.
     *
     * @param string   $to
     * @param string   $subject
     * @param string   $body
     * @param string[] $attachments Paths, missing files are skipped.
     * @return bool
     * @throws Exception
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool
    {
        return $this->to($to)->subject($subject)->message($body)->attach($attachments)->send();
    }
}
