<?php
namespace Grafema;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * A Mail class for sending emails using PHPMailer.
 * This class provides a static method to send emails with support for
 * attachments, custom headers, and multiple recipients.
 *
 * @since 2025.1
 */
final class Mail
{

	/**
	 * Sends an email.
	 *
	 * This method constructs an email message and sends it to one or
	 * more recipients using the PHPMailer library. It supports
	 * attachments and custom headers. The method will handle the
	 * necessary configurations for sending the email.
	 *
	 * @param string $to         Recipient(s) email address(es).
	 *                           Can be a comma-separated string or an array.
	 * @param string $subject    Subject of the email.
	 * @param string $message    The body of the email.
	 * @param string $headers    Optional. Custom headers for the email.
	 *                           Can be a string or an array. Default is an empty string.
	 * @param array $attachments Optional. An array of file paths to be
	 *                           attached to the email. Default is an empty array.
	 *
	 * @return bool Returns true if the email was sent successfully, false otherwise.
	 *
	 * @throws Exception If an error occurs during the sending process.
	 *
	 * @since 2025.1
	 */
    public static function send(string $to, string $subject, string $message, string $headers = '', array $attachments = [])
    {
        $atts = compact('to', 'subject', 'message', 'headers', 'attachments');

        if (isset($atts['to'])) {
            $to = $atts['to'];
        }

        if ( ! is_array($to)) {
            $to = explode(',', $to);
        }

        if (isset($atts['subject'])) {
            $subject = $atts['subject'];
        }

        if (isset($atts['message'])) {
            $message = $atts['message'];
        }

        if (isset($atts['headers'])) {
            $headers = $atts['headers'];
        }

        if (isset($atts['attachments'])) {
            $attachments = $atts['attachments'];
        }

        if ( ! is_array($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
        }
        global $phpmailer;

        // (Re)create it, if it's gone missing.
        if ( ! $phpmailer instanceof PHPMailer) {
            require_once GRFM_CORE . 'Mail/PHPMailer/Exception.php';
            require_once GRFM_CORE . 'Mail/PHPMailer/PHPMailer.php';
            require_once GRFM_CORE . 'Mail/PHPMailer/SMTP.php';
            $phpmailer = new PHPMailer(true);

            $phpmailer::$validator = static function ($email) {
                return Is::email($email);
            };
        }

        // Headers.
        $cc = [];
        $bcc = [];
        $reply_to = [];

        if (empty($headers)) {
            $headers = [];
        } else {
            if ( ! is_array($headers)) {
                // Explode the headers out, so this function can take
                // both string headers and an array of headers.
                $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
            } else {
                $tempheaders = $headers;
            }
            $headers = [];

            // If it's actually got contents.
            if ( ! empty($tempheaders)) {
                // Iterate through the raw headers.
                foreach ((array) $tempheaders as $header) {
                    if (strpos($header, ':') === false) {
                        if (stripos($header, 'boundary=') !== false) {
                            $parts = preg_split('/boundary=/i', trim($header));
                            $boundary = trim(str_replace(["'", '"'], '', $parts[1]));
                        }
                        continue;
                    }
                    // Explode them out.
                    [$name, $content] = explode(':', trim($header), 2);

                    // Cleanup crew.
                    $name = trim($name);
                    $content = trim($content);

                    switch (strtolower($name)) {
                        // Mainly for legacy -- process a "From:" header if it's there.
                        case 'from':
                            $bracket_pos = strpos($content, '<');
                            if ($bracket_pos !== false) {
                                // Text before the bracketed email is the "From" name.
                                if ($bracket_pos > 0) {
                                    $from_name = substr($content, 0, $bracket_pos - 1);
                                    $from_name = str_replace('"', '', $from_name);
                                    $from_name = trim($from_name);
                                }

                                $from_email = substr($content, $bracket_pos + 1);
                                $from_email = str_replace('>', '', $from_email);
                                $from_email = trim($from_email);

                                // Avoid setting an empty $from_email.
                            } elseif (trim($content) !== '') {
                                $from_email = trim($content);
                            }
                            break;
                        case 'content-type':
                            if (strpos($content, ';') !== false) {
                                [$type, $charset_content] = explode(';', $content);
                                $content_type = trim($type);
                                if (stripos($charset_content, 'charset=') !== false) {
                                    $charset = trim(str_replace(['charset=', '"'], '', $charset_content));
                                } elseif (stripos($charset_content, 'boundary=') !== false) {
                                    $boundary = trim(str_replace(['BOUNDARY=', 'boundary=', '"'], '', $charset_content));
                                    $charset = '';
                                }

                                // Avoid setting an empty $content_type.
                            } elseif (trim($content) !== '') {
                                $content_type = trim($content);
                            }
                            break;
                        case 'cc':
                            $cc = array_merge((array) $cc, explode(',', $content));
                            break;
                        case 'bcc':
                            $bcc = array_merge((array) $bcc, explode(',', $content));
                            break;
                        case 'reply-to':
                            $reply_to = array_merge((array) $reply_to, explode(',', $content));
                            break;
                        default:
                            // Add it to our grand headers array.
                            $headers[trim($name)] = trim($content);
                            break;
                    }
                }
            }
        }

        // Empty out the values that may be set.
        $phpmailer->clearAllRecipients();
        $phpmailer->clearAttachments();
        $phpmailer->clearCustomHeaders();
        $phpmailer->clearReplyTos();

        // Set "From" name and email.
        // If we don't have a name from the input headers.
        if ( ! isset($from_name)) {
            $from_name = 'Grafema';
        }

        /*
         * If we don't have an email from the input headers, default to core@$sitename
         * Some hosts will block outgoing mail from this address if it doesn't exist,
         * but there's no easy alternative. Defaulting to admin_email might appear to be
         * another option, but some hosts may refuse to relay mail from an unknown domain.
         */
        if ( ! isset($from_email)) {
            // Get the site domain and get rid of www.
            $sitename = parse_url($_SERVER['SCRIPT_URI'], PHP_URL_HOST);
            if (substr($sitename, 0, 4) === 'www.') {
                $sitename = substr($sitename, 4);
            }

            $from_email = 'core@' . $sitename;
        }

        try {
            $phpmailer->setFrom($from_email, $from_name, false);
        } catch (Exception $e) {
            $mail_error_data = compact('to', 'subject', 'message', 'headers', 'attachments');
            $mail_error_data['phpmailer_exception_code'] = $e->getCode();
            return false;
        }

        // Set mail's subject and body.
        $phpmailer->Subject = $subject;
        $phpmailer->Body = $message;

        // Set destination addresses, using appropriate methods for handling addresses.
        $address_headers = compact('to', 'cc', 'bcc', 'reply_to');

        foreach ($address_headers as $address_header => $addresses) {
            if (empty($addresses)) {
                continue;
            }

            foreach ((array) $addresses as $address) {
                try {
                    // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
                    $recipient_name = '';

                    if (preg_match('/(.*)<(.+)>/', $address, $matches)) {
                        if (count($matches) === 3) {
                            $recipient_name = $matches[1];
                            $address = $matches[2];
                        }
                    }

                    switch ($address_header) {
                        case 'to':
                            $phpmailer->addAddress($address, $recipient_name);
                            break;
                        case 'cc':
                            $phpmailer->addCc($address, $recipient_name);
                            break;
                        case 'bcc':
                            $phpmailer->addBcc($address, $recipient_name);
                            break;
                        case 'reply_to':
                            $phpmailer->addReplyTo($address, $recipient_name);
                            break;
                    }
                } catch ( Exception $e ) {
                    continue;
                }
            }
        }

        // Set to use PHP's mail().
        $phpmailer->isMail();

        // Set Content-Type and charset.

        // If we don't have a content-type from the input headers.
        if ( ! isset($content_type)) {
            $content_type = 'text/html';
        }

        $phpmailer->ContentType = $content_type;

        // Set whether it's plaintext, depending on $content_type.
        if ($content_type === 'text/html') {
            $phpmailer->isHTML(true);
        }

        // If we don't have a charset from the input headers.
        if ( ! isset($charset)) {
            $charset = 'utf-8';
        }

        $phpmailer->CharSet = $charset;
        // Set custom headers.
        if ( ! empty($headers)) {
            foreach ((array) $headers as $name => $content) {
                // Only add custom headers not added automatically by PHPMailer.
                if ( ! in_array($name, ['MIME-Version', 'X-Mailer'], true)) {
                    try {
                        $phpmailer->addCustomHeader(sprintf('%1$s: %2$s', $name, $content));
                    } catch ( Exception $e ) {
                        continue;
                    }
                }
            }

            if (stripos($content_type, 'multipart') !== false && ! empty($boundary)) {
                $phpmailer->addCustomHeader(sprintf('Content-Type: %s; boundary="%s"', $content_type, $boundary));
            }
        }

        if ( ! empty($attachments)) {
            foreach ($attachments as $attachment) {
                try {
                    $phpmailer->addAttachment($attachment);
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        // Send!
        try {
            return $phpmailer->send();
        } catch (Exception $e) {
            $mail_error_data = compact('to', 'subject', 'message', 'headers', 'attachments');
            $mail_error_data['phpmailer_exception_code'] = $e->getCode();
            return false;
        }
    }
}
