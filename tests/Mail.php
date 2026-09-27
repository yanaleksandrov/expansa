<?php

declare(strict_types=1);

use Expansa\Mail\Manager;
use PHPMailer\PHPMailer\PHPMailer;

// run: php tests/Mail.php
require_once __DIR__ . '/bootstrap.php';

final class FakeMailer extends PHPMailer
{
    public static array $sent = [];

    public function send(): bool
    {
        self::$sent[] = [$this->getToAddresses()[0][0], $this->Subject, $this->Body, count($this->getAttachments()), $this->getCustomHeaders()];

        return true;
    }
}

$file = tempnam(sys_get_temp_dir(), 'mail');

// the double keeps what the email was built with and sends nothing
$fake = function (PHPMailer $mailer): PHPMailer {
    $fake = new FakeMailer();
    $fake->addAddress($mailer->getToAddresses()[0][0]);
    $fake->Subject = $mailer->Subject;
    $fake->Body    = $mailer->Body;
    foreach ($mailer->getAttachments() as $attachment) {
        $fake->addAttachment($attachment[0]);
    }
    foreach ($mailer->getCustomHeaders() as [$name, $value]) {
        $fake->addCustomHeader($name, $value);
    }

    return $fake;
};

$mail = new Manager();
$mail->configure(setup: $fake);
check('send() passes the email through the setup callback', $mail->send('a@example.com', 'Hi', 'Body', [$file]) && FakeMailer::$sent[0] === ['a@example.com', 'Hi', 'Body', 1, []]);

FakeMailer::$sent = [];
$mail->to('b@example.com')->headers("X-One: 1\nX-Two: 2")->attach([$file, '/no/such/file'])->send();
check('attach() skips missing files, headers() adds a header per line', FakeMailer::$sent[0][3] === 1 && FakeMailer::$sent[0][4] === [['X-One', '1'], ['X-Two', '2']]);

$mail->configure(setup: function (PHPMailer $mailer) {
    $mailer->Mailer = 'smtp';
    $mailer->Host   = '127.0.0.1';
    $mailer->Port   = 1;

    return $mailer;
});
$mailer = $mail->to('c@example.com')->subject('x')->message('y');
check('a failed send returns false with the error', ! $mailer->send() && $mailer->error !== '');

unlink($file);

exit($failures > 0 ? 1 : 0);
