<?php

declare(strict_types=1);

namespace Expansa\Mail;

use Expansa\Hook;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once EX_CORE . 'Mail/PHPMailer/Exception.php';
require_once EX_CORE . 'Mail/PHPMailer/PHPMailer.php';
require_once EX_CORE . 'Mail/PHPMailer/SMTP.php';

class Mailer
{
    public function __construct(
        private PHPMailer $mailer = new PHPMailer()
    ) {} // phpcs:ignore

    /**
     * @throws Exception
     */
    public function to(string $email): self
    {
        $this->mailer->addAddress($email);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function from(string $email): self
    {
        $this->mailer->setFrom($email);

        return $this;
    }

    public function replyTo(string $email): self
    {

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->mailer->Subject = $subject;
        return $this;
    }

    public function message(string $message): self
    {
        $this->mailer->Body = $message;
        return $this;
    }

    public function headers(string $headers): self
    {

        return $this;
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
