<?php
/**
 * Email wrapper template can be overridden by copying it to themes/yourtheme/dashboard/views/mails/wrapper.php
 *
 * @var App\Support\Site $site Settings of the site: name, language, charset, addresses, version; shared with every view.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$template = trim($__data['body_template'] ?? '');
if (empty($template) || ! $__env->exists($template)) {
    return false;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ $site->locale }}">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>
<body style="margin: 20px 0; padding: 0; width: 100%; box-sizing: border-box; background-color: #fbfbfd; color: #3f536e; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table border="0" cellpadding="10" cellspacing="0" style="width: 90%; max-width:560px; padding: 0 0 20px; margin: 0 auto;">
        <tbody>
            <tr>
                <td style="text-align: center;">
                    <img src="{{ url('dashboard/assets/images/logo-grid.png') }}" width="212" height="124" alt="{{ $site->name }}" style="display: inline-block; border: 0;">
                </td>
            </tr>
        </tbody>
    </table>
    <table border="0" cellpadding="0" cellspacing="0" style="width: 90%; max-width: 560px; margin: 0 auto; background: #fff; border-radius: 4px; overflow: hidden; border: 1px solid #e6e7e9; border-top: 4px solid #206bc4;">
        <tr>
            <?php echo view($template, $__data); ?>
        </tr>
    </table>
    <table border="0" cellpadding="10" cellspacing="0" style="width: 90%; max-width: 560px; padding: 20px 0 0; margin: 0 auto;">
        <tbody>
            <tr>
                <td style="opacity: 0.75; font-size: 11px; line-height: 145%; text-align: center;">
                    <p>{!! t('This message was sent automatically, please do not reply to it.') !!}</p>
                    <p>{!! t('© :currentYear :companyName. All rights reserved.', date('Y'), $site->name) !!}</p>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
