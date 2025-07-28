<?php

declare(strict_types=1);

namespace Expansa\Mail;

use Expansa\Facades\Hook;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once EX_CORE . 'Mail/PHPMailer/Exception.php';
require_once EX_CORE . 'Mail/PHPMailer/PHPMailer.php';
require_once EX_CORE . 'Mail/PHPMailer/SMTP.php';

/**
 * Class Mailer
 *
 * A wrapper around PHPMailer providing a fluent interface
 * for constructing and sending emails with support for
 * attachments, custom headers, and hooks for configuration.
 */
class Mailer
{
    /**
     * Mailer constructor.
     *
     * @param PHPMailer $mailer An instance of PHPMailer
     */
    public function __construct(
        private PHPMailer $mailer = new PHPMailer()
    ) {} // phpcs:ignore

    /**
     * Add a recipient email address.
     *
     * @param string $email Recipient email address
     * @return $this Fluent interface
     * @throws Exception If adding address fails
     */
    public function to(string $email): self
    {
        $this->mailer->addAddress($email);

        return $this;
    }

    /**
     * Set the sender's email address.
     *
     * @param string $email Sender email address
     * @return $this Fluent interface
     * @throws Exception If setting from address fails
     */
    public function from(string $email): self
    {
        $this->mailer->setFrom($email);

        return $this;
    }

    /**
     * Set the reply-to email address.
     *
     * @param string $email Reply-to email address
     * @return $this Fluent interface
     */
    public function replyTo(string $email): self
    {

        return $this;
    }

    /**
     * Set the subject of the email.
     *
     * @param string $subject Email subject
     * @return $this Fluent interface
     */
    public function subject(string $subject): self
    {
        $this->mailer->Subject = $subject;
        return $this;
    }

    /**
     * Set the body message of the email.
     *
     * @param string $message Email body content
     * @return $this Fluent interface
     */
    public function message(string $message): self
    {
        $this->mailer->Body = $message;
        return $this;
    }

    /**
     * Set custom headers for the email.
     *
     * @param string $headers Custom headers as a string
     * @return $this Fluent interface
     */
    public function headers(string $headers): self
    {

        return $this;
    }

    /**
     * Attach files to the email.
     *
     * @param array $attachments List of file paths to attach
     * @return $this Fluent interface
     * @throws Exception If adding attachments fails
     */
    public function attach(array $attachments): self
    {
        foreach ($attachments as $attachment) {
            if (!is_file($attachment)) {
                continue;
            }
            $this->mailer->addAttachment($attachment);
        }
        return $this;
    }

    /**
     * Send the email.
     *
     * Applies any mailer configuration hooks, disables SSL peer verification
     * for self-signed certificates, and attempts to send the email.
     *
     * @return PHPMailer|true Returns true on success, or PHPMailer instance on failure
     * @throws Exception If sending the email fails internally
     */
    public function send(): PHPMailer|true
    {
        $this->mailer = Hook::call('expansaConfigureMailer', $this->mailer);

        $this->mailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        if (!$this->mailer->send()) {
            return $this->mailer;
        }
        return true;
    }
}
