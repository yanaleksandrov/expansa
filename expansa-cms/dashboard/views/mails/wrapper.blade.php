<?php

use App\Option;

/**
 * Email wrapper template can be overridden by copying it to themes/yourtheme/dashboard/views/mail/wrapper.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$template = trim($__data['body_template'] ?? '');
if (empty($template) || !is_file($template)) {
    return false;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo Option::get( 'charset' ); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>
<body style="margin: 20px 0; padding: 0; width: 100%; box-sizing: border-box; background-color: #fbfbfd; color: #3f536e; line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table border="0" cellpadding="10" cellspacing="0" style="width: 90%; max-width:560px; padding: 0 0 20px; margin: 0 auto;">
        <tbody>
            <tr>
                <td style="text-align: center;">
                    <svg width="160" height="42" viewBox="0 0 160 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M27.8 37h-9.9v-4.3a9.6 9.6 0 0 1-2.9 3.5c-1.2 1-3 1.4-5 1.4a9 9 0 0 1-7.3-3.2A13 13 0 0 1 0 25.8c0-3.5.9-6.5 2.6-9 1.7-2.4 4.4-3.6 7.9-3.6 1.9 0 3.4.3 4.6 1.1a8 8 0 0 1 2.8 3V6.3h-4.6V1.3h11.4V32h3v5Zm-9.9-11.8v-.5c0-2-.5-3.5-1.5-4.6a5 5 0 0 0-3.8-1.6c-1.5 0-2.8.5-4 1.6C7.6 21.2 7 23 7 25.4c0 2.2.5 4 1.5 5.1 1 1.2 2.3 1.8 3.7 1.8 1.8 0 3.2-.7 4.2-2.2 1-1.5 1.5-3.1 1.5-4.9ZM33 9.5v-7h6.8v7H33ZM43.2 37h-13v-5h3.2V18.6H30v-5H40V32h3.1v5ZM74.3 37h-13v-5h3.2V24c0-2-.4-3.5-1-4.3-.7-1-1.7-1.3-3-1.3-1.6 0-2.9.5-3.7 1.5-.9 1-1.4 2.2-1.4 3.8V32h3.1v5h-13v-5h3.2V18.7h-3.4v-5h10.1v3.5c1.6-2.7 4-4 7.6-4 2.2 0 4.1.6 5.7 2 1.7 1.3 2.5 3.3 2.5 6V32h3.1v5ZM136.6 37h-10v-3.5c-1.6 2.7-4.1 4-7.6 4-2.2 0-4.1-.6-5.8-2-1.6-1.2-2.4-3.3-2.4-6V18.6h-3.1v-5h9.8v13.1c0 2 .3 3.5 1 4.4.7.8 1.7 1.3 3 1.3 1.6 0 2.8-.6 3.7-1.6.9-1 1.3-2.4 1.3-4v-8.2h-3.7v-5h10.5V32h3.3v5ZM160 30c0 2.5-1 4.5-3.1 5.7-2 1.3-4.7 2-8 2-1.7 0-3.5-.3-5.2-.6-1.7-.4-3.3-1-4.9-1.8l1-6.5 5.2.5v3a9 9 0 0 0 3.5.4c1.3 0 2.4-.2 3.3-.6 1-.4 1.4-1 1.4-1.9 0-1-.7-1.6-2.1-2l-5-.9c-1.8-.3-3.5-1-4.9-2-1.4-.9-2.1-2.5-2.1-4.9 0-2.8 1.1-4.8 3.3-5.7a17 17 0 0 1 7-1.5c3.1 0 6 .7 8.8 2l.6 6-5.3.6-.4-3.2c-1.1-.4-2.3-.6-3.7-.6-.8 0-1.6.2-2.2.6-.6.3-.9.9-.9 1.6 0 1 .7 1.7 2 2 1.4.4 3 .7 4.8 1 1.9.3 3.5 1 4.8 2 1.4.9 2.1 2.5 2.1 4.7Z" fill="#000"/>
                        <path d="m89 39.7-7.7-2.5 4.4-11.6v-6.7l-5-11.8 8-2.7 3.1 12.3 9.8-8.2 4.7 6.6L96 22l10.7 6.7-4.7 6.6-9.8-8-3 12.5Z" fill="red"/>
                        <path d="m85.7 25.6-12.5 1-.2-8.4 12.8.7-.1 6.7Z" fill="#000"/>
                    </svg>
                </td>
            </tr>
        </tbody>
    </table>
    <table border="0" cellpadding="0" cellspacing="0" style="width: 90%; max-width: 560px; margin: 0 auto; background: #fff; border-radius: 4px; overflow: hidden; border: 1px solid #e6e7e9; border-top: 4px solid #206bc4;">
        <tr>
            <?php echo view( $template, $__data ); ?>
        </tr>
    </table>
    <table border="0" cellpadding="10" cellspacing="0" style="width: 90%; max-width: 560px; padding: 20px 0 0; margin: 0 auto;">
        <tbody>
            <tr>
                <td style="opacity: 0.75; font-size: 11px; line-height: 145%; text-align: center;">
                    <p>This message is generated automatically. Don't reply it.</p>
                    <p>All rights reserved © 2005-2023</p>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
