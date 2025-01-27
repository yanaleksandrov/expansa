<?php

use Expansa\Builders\Tree;
use Expansa\Disk;

/**
 * File manager table
 *
 * This template can be overridden by copying it to themes/yourtheme/file-manager/templates/file-manager.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */

if (! defined('EX_PATH')) {
    exit;
}

$directories = Disk::dir(EX_PATH)->read();
$folders     = Disk::dir(EX_PATH)->read();
$files       = [
    ...Disk::dir(EX_PATH)->files('.[!.]*'),
    ...Disk::dir(EX_PATH)->files('*.*'),
];
?>
<div class="expansa-main">
    <div class="fm">
        <div class="fm-header">
            <ul class="fm-header-list">
                <li class="fm-header-item" title="<?php echo t_attr('Go to parent folder'); ?>">
                    <button class="btn btn--icon btn--outline btn--xs">
	                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
                            <g stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3.837 12.796h6.326c.67 0 1.24-.488 1.341-1.15.162-1.051.2-2.117.112-3.176h1.224a.5.5 0 0 0 .422-.768l-.212-.333a20 20 0 0 0-4.785-5.205l-.66-.5a1 1 0 0 0-1.21 0l-.66.5A20 20 0 0 0 .95 7.37l-.212.333a.5.5 0 0 0 .422.768h1.224a13.566 13.566 0 0 0 .112 3.176c.102.662.671 1.15 1.34 1.15Z"/>
                                <path d="M7 8.089c.921 0 1.668.746 1.668 1.667v3.04H5.333v-3.04c0-.92.746-1.667 1.667-1.667Z"/>
                            </g>
                        </svg>
                    </button>
                </li>
                <li class="fm-header-item" title="<?php echo t_attr('Back'); ?>">
                    <button class="btn btn--icon btn--outline btn--xs"><i class="ph ph-caret-left"></i></button>
                </li>
                <li class="fm-header-item" title="<?php echo t_attr('Next'); ?>">
                    <button class="btn btn--icon btn--outline btn--xs"><i class="ph ph-caret-right"></i></button>
                </li>
                <li class="fm-header-item" title="<?php echo t_attr('Update'); ?>">
                    <button class="btn btn--icon btn--outline btn--xs"><i class="ph ph-arrows-clockwise"></i></button>
                </li>
            </ul>
            <ul class="fm-header-list">
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-file-plus"></i> <?php echo t('Add file'); ?></button>
                </li>
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-folder-plus"></i> <?php echo t('Add folder'); ?></button>
                </li>
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-upload-simple"></i> <?php echo t('Upload'); ?></button>
                </li>
            </ul>
            <ul class="fm-header-list">
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-copy"></i> <?php echo t('Copy'); ?></button>
                </li>
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-scissors"></i> <?php echo t('Cut'); ?></button>
                </li>
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-download-simple"></i> <?php echo t('Download'); ?></button>
                </li>
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-textbox"></i> <?php echo t('Rename'); ?></button>
                </li>
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-trash-simple"></i> <?php echo t('Delete'); ?></button>
                </li>
            </ul>
            <ul class="fm-header-list">
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
                            <g stroke="#000" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6.331 1.688a.991.991 0 0 0-.883-.648 26.605 26.605 0 0 0-3.456.033 1.004 1.004 0 0 0-.92.893C.735 4.903.623 7.85.975 10.792c.119 1 .937 1.725 1.915 1.832 2.762.3 5.485.293 8.247-.008a2.092 2.092 0 0 0 1.845-1.823c.218-1.82.276-3.684.14-5.971a1.906 1.906 0 0 0-1.745-1.788c-1.344-.112-2.279-.118-3.931-.153a.998.998 0 0 1-.915-.652l-.2-.54ZM8 5.981h2.5M8 8.981h2.5M9.248 2.934V8.98"/>
                            </g>
                        </svg>
                        <?php echo t('Archive'); ?>
                    </button>
                </li>
                <li class="fm-header-item">
                    <button class="btn btn--outline btn--xs"><i class="ph ph-file-zip"></i> <?php echo t('Extract files'); ?></button>
                </li>
            </ul>
        </div>

        <div class="fm-top">
            <div class="df aic g-1">
                <i class="ph ph-folder-simple-dashed"></i> <code>public_html</code>/<code>dev</code>/<code>cody</code>/<code>list</code>
            </div>
            <div class="df aic g-3">
                <?php echo t(':count items', count([ ...$folders, ...$files ])); ?>
                <div class="field field--xs field--outline">
                    <label class="field-item">
                        <input type="search" name="search" placeholder="Search">
                    </label>
                </div>
            </div>
        </div>
        <div class="fm-main">
            <div class="fm-folders">
                <?php echo Tree::build($directories, function (int $depth, $directory) { ?>
                    <ul class="fm-folders-list" data-depth="<?php echo $depth; ?>">
                        <li class="fm-folder-item">
                            <span class="fm-folder-item-name"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
                              <g>
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M6.331 1.688a.991.991 0 0 0-.883-.648 26.605 26.605 0 0 0-3.456.033 1.004 1.004 0 0 0-.92.893C.735 4.903.623 7.85.975 10.792c.119 1 .937 1.725 1.915 1.832 2.762.3 5.485.293 8.247-.008a2.092 2.092 0 0 0 1.845-1.823c.218-1.82.276-3.684.14-5.971a1.906 1.906 0 0 0-1.745-1.788c-1.344-.112-2.279-.118-3.931-.153a.998.998 0 0 1-.915-.652l-.2-.54Z"/>
                              </g>
                            </svg> <?php echo $directory; ?></span>
                            @nested
                        </li>
                    </ul>
                <?php }); ?>
            </div>
            <div class="fm-files">
                <div class="fm-files-head">
                    <div>Name</div>
                    <div>Modified</div>
                    <div>Permissions</div>
                    <div>Size</div>
                </div>
                <?php foreach ($folders as $folder) : ?>
                    <label class="fm-files-item" data-type="folder">
                        <span><input name="file" type="checkbox" value="">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
                              <g>
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M6.331 1.688a.991.991 0 0 0-.883-.648 26.605 26.605 0 0 0-3.456.033 1.004 1.004 0 0 0-.92.893C.735 4.903.623 7.85.975 10.792c.119 1 .937 1.725 1.915 1.832 2.762.3 5.485.293 8.247-.008a2.092 2.092 0 0 0 1.845-1.823c.218-1.82.276-3.684.14-5.971a1.906 1.906 0 0 0-1.745-1.788c-1.344-.112-2.279-.118-3.931-.153a.998.998 0 0 1-.915-.652l-.2-.54Z"/>
                              </g>
                            </svg>
                            <?php echo basename($folder); ?>
                        </span>
                        <span>Sep 17, 2023 08:35 PM</span>
                        <span title="Read & Write"><?php echo substr(sprintf('%o', fileperms($folder)), -4); ?></span>
                        <span>177 KB</span>
                    </label>
                <?php endforeach; ?>
                <?php foreach ($files as $file) : ?>
                    <label class="fm-files-item" data-type="file">
                        <span><input name="file" type="checkbox" value=""><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 14 14">
  <g stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12.48 5.89H8.792C8.086 1.886 9.866.97 10.906 1c2.16.337 1.985 3.4 1.575 4.89Z"/>
    <path d="M8.225 12.52C10.873 9.432 6.302 2.294 10.551 1h-6.96c-4.071 1.294.144 8.67-2.326 11.275 0 0 .84.725 3.418.725 2.577 0 3.542-.48 3.542-.48ZM4.337 3.94h1.855M4.577 7h1.855M4.577 10.062h1.855"/>
  </g>
</svg> <?php echo basename($file); ?></span>
                        <span><?php echo date('M d, Y h:i A', filemtime($file)); ?></span>
                        <span title="Read & Write"><?php echo substr(sprintf('%o', fileperms($file)), -4); ?></span>
                        <span><?php echo \Expansa\Support\Humanize::fromBytes(filesize($file)); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
