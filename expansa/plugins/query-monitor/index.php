<?php

use Expansa\Db;
use Expansa\I18n;
use Expansa\Is;
use Expansa\Hook;
use Expansa\Debug;
use Expansa\Extensions\Plugin;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setVersion('2024.9')
            ->setName('Query Monitor')
            ->setAuthor('Expansa Team')
            ->setDescription(I18n::_t('The developer tools panel for Expansa'));
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

        Hook::add('expansa_dashboard_footer', function () {
            ?>
            <template x-teleport="#query">
                <a class="menu__link" x-show="query" href="#">
                    <i class="ph ph-monitor"></i> <?php printf('%s %s %sQ', Debug::timer('getall'), Debug::memory_peak(), Db::queries()); ?>
                </a>
            </template>
            <?php
        });
    }

    public function activate(): void
    {
        // TODO: Implement activate() method.
    }

    public function deactivate(): void
    {
        // TODO: Implement deactivate() method.
    }

    public function install(): void
    {
        // TODO: Implement install() method.
    }

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
};
