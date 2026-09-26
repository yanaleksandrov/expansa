<?php

declare(strict_types=1);

use Expansa\Extensions\Plugin;
use Expansa\Facades\Hook;
use Expansa\Facades\Lifecycle;
use Expansa\Support\Is;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setVersion('2024.9')
            ->setName('Query Monitor')
            ->setAuthor('Expansa Team')
            ->setDescription(t('The developer tools panel for Expansa'));
    }

    /**
     * This is sample function.
     *
     * This is big description of current function.
     * End second...
     *
     * @param mixed  $str     Some parameter description
     * @param bool   $ret     Some return parameter. Default true.
     * @param string $content Description. Default null. If edit,
     *                        and other content.
     *                        Start new paragraph!
     * @param bool   $after   Description
     *
     * @category              CategoryName
     *
     * @copyright  1997-2005 The PHP Group
     * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
     *
     * @version    SVN: $Id$
     *
     * @global     string $gStr глобальная строчная переменная
     *
     * @see       http://pear.php.net/package/PackageName Указывает ссылку на документацию элемента.
     * @since      1.2.0
     * @deprecated 2.0.0
     * @see        wp_signon()
     */
    public function test(mixed $str, bool $ret, string $content, $after): void
    {
    }

    public function boot(): void
    {
        if (! Is::dashboard()) {
            return;
        }

        // lifecycle timeline for browser devtools (Network → Timing), sent before the page is output
        Hook::add('dashboardLoaded', function (string $content): string {
            if (! headers_sent()) {
                $metrics = array_map(
                    fn (array $step) => sprintf('%s-%s;dur=%s', $step['type'], $step['name'], $step['time']),
                    Lifecycle::timeline()
                );

                header('Server-Timing: ' . implode(', ', $metrics));
            }

            return $content;
        });
    }

    public function activate(): void
    {

    }

    public function deactivate(): void
    {

    }

    public function install(): void
    {

    }

    public function uninstall(): void
    {

    }
};
