<?php

use Plugins\FileManager\Settings;

/**
 * File manager: actions, navigation, the folder tree, entries of the current folder or the trash,
 * the details panel and the dialogs. The state lives in `Youla.data('fileManager')`,
 * plugins/file-manager/assets/js/main.js; the template makes the requests.
 *
 * @var string               $root     Name of the root folder.
 * @var Settings             $settings
 * @var array<string, mixed> $listing  Listing of the folder from `?path=`, see Explorer::list().
 *
 * @package Expansa\Templates
 */

defined('EX_PATH') || exit;

$js = static fn (string $text): string => escape(json_encode(htmlspecialchars_decode(t($text), ENT_QUOTES), JSON_UNESCAPED_UNICODE));

$list = static fn (string $path, string $then = 'show(data)'): string => "\$ajax.post('file-manager/list', {path: $path}).then(data => $then)";

$details = "\$ajax.post('file-manager/details', {path, name: selected[0]}).then(data => describe(data))";

$ask = static fn (string $title, string $label, string $value, string $request, string $then = 'show(data)'): string => "\$dialog.open('tmpl-file-manager-name', {"
    . "class: 'dialog--sm', title: {$js($title)}, label: {$js($label)}, action: {$js($title)}, value: $value, "
    . "submit: name => $request.then(data => { $then; })})";

$confirm = static fn (string $title, string $message, string $names, string $request, string $then = 'show(data)'): string => "\$dialog.open('tmpl-file-manager-confirm', {"
    . "class: 'dialog--sm', title: {$js($title)}, message: {$js($message)}, names: $names, action: {$js($title)}, "
    . "submit: () => $request.then(data => { $then; })})";

$conflict = static fn (string $run): string => "(run => taken.length ? \$dialog.open('tmpl-file-manager-conflict', {"
    . "class: 'dialog--sm', title: {$js('Some names are taken')}, names: taken, submit: run}) : run('keep'))($run)";

$upload = static fn (string $files, string $to): string => "(files => \$ajax.post('file-manager/conflicts', {to: $to, names: fileNames(files)}).then(({names: taken}) => "
    . $conflict("conflict => \$ajax.post('file-manager/upload', {path, to: $to, conflict, ...uploadPayload(files)}, event => progress(event)).then(data => show(data)).finally(() => progress(null))")
    . "))($files)";

$transfer = static fn (string $from, string $names, string $to, string $move, string $then = 'show(data)'): string => "((from, names, to, move) => "
    . "(from === to && !move ? Promise.resolve({names: []}) : \$ajax.post('file-manager/conflicts', {to, names})).then(({names: taken}) => "
    . $conflict("conflict => \$ajax.post('file-manager/paste', {path, from, names, to, move, conflict}).then(data => { $then; })")
    . "))($from, $names, $to, $move)";

$drop = static fn (string $to): string => "editable() && (\$event.dataTransfer.files.length ? " . $upload('[...$event.dataTransfer.files]', $to)
    . " : dragging.length && " . $transfer('path', 'JSON.stringify(dragging)', $to, '1') . ")";

$preview = "kind(item.name) === 'text' "
    . "? \$ajax.post('file-manager/read', {path, name: item.name}).then(({text}) => \$dialog.open('tmpl-file-manager-preview', {class: 'dialog--lg', title: item.name, kind: 'text', text})) "
    . ": \$dialog.open('tmpl-file-manager-preview', {class: 'dialog--lg', title: item.name, kind: kind(item.name), url: previewUrl(item.name)})";

$open = "mode === 'trash' ? null : (searching "
    . "? (item.folder ? " . $list('join(item.dir, item.name)') . " : " . $list('item.dir', 'show(data), setSelected([item.name])') . ") "
    . ": (item.folder ? " . $list('join(path, item.name)') . " : $preview))";

$config = ['readonly' => $settings->readonly, 'trash' => $settings->trash];
?>
<div class="fm" u-data="fileManager"
     @load="configure(<?php echo escape(json_encode($config)); ?>); show(<?php echo escape(json_encode($listing, JSON_UNESCAPED_UNICODE)); ?>)"
     @popstate.window="<?php echo $list('locate()', "show(data, 'pop')"); ?>">
    <div class="fm-header" u-show="mode === 'files'">
        <ul class="fm-header-list" u-show="!readonly">
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable()" @click="<?php echo $ask('Add file', 'File name', "''", "\$ajax.post('file-manager/create-file', {path, name})"); ?>">
                    <i class="ph ph-file-plus"></i> <?php echo t('Add file'); ?>
                </button>
            </li>
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable()" @click="<?php echo $ask('Add folder', 'Folder name', "''", "\$ajax.post('file-manager/create-folder', {path, name})"); ?>">
                    <i class="ph ph-folder-plus"></i> <?php echo t('Add folder'); ?>
                </button>
            </li>
            <li class="fm-header-item">
                <label class="btn btn--outline btn--xs" :class="!editable() && 'is-disabled'">
                    <i class="ph ph-upload-simple"></i> <?php echo t('Upload'); ?>
                    <input type="file" multiple hidden :disabled="!editable()" @change="<?php echo $upload('[...$el.files]', 'path'); ?>; $el.value = ''">
                </label>
            </li>
        </ul>
        <ul class="fm-header-list" u-show="!readonly">
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable() || !selected.length" @click="copy()">
                    <i class="ph ph-copy"></i> <?php echo t('Copy'); ?>
                </button>
            </li>
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable() || !selected.length" @click="copy(true)">
                    <i class="ph ph-scissors"></i> <?php echo t('Cut'); ?>
                </button>
            </li>
            <li class="fm-header-item" u-show="clipboard" hidden>
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable()" @click="<?php echo $transfer('clipboard.from', 'clipboard.names', 'path', 'clipboard.move', 'show(data), pasted()'); ?>">
                    <i class="ph ph-clipboard"></i> <?php echo t('Paste'); ?> (<span u-text="clipboard?.count"></span>)
                </button>
            </li>
        </ul>
        <ul class="fm-header-list">
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="searching || !selected.length" @click="download()">
                    <i class="ph ph-download-simple"></i> <?php echo t('Download'); ?>
                </button>
            </li>
            <li class="fm-header-item" u-show="!readonly">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable() || selected.length !== 1" @click="<?php echo $ask('Rename', 'New name', 'selected[0]', "\$ajax.post('file-manager/rename', {path, name: selected[0], to: name})"); ?>">
                    <i class="ph ph-textbox"></i> <?php echo t('Rename'); ?>
                </button>
            </li>
            <li class="fm-header-item" u-show="!readonly">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable() || !selected.length" @click="<?php echo $confirm('Delete', $settings->trash ? 'Move the selected items to the trash?' : 'Delete the selected items for good?', '[...selected]', "\$ajax.post('file-manager/delete', {path, names: names()})"); ?>">
                    <i class="ph ph-trash-simple"></i> <?php echo t('Delete'); ?>
                </button>
            </li>
        </ul>
        <ul class="fm-header-list" u-show="!readonly">
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable() || !selected.length" @click="<?php echo $ask('Archive', 'Archive name', "'archive.zip'", "\$ajax.post('file-manager/archive', {path, names: names(), name})"); ?>">
                    <i class="ph ph-archive"></i> <?php echo t('Archive'); ?>
                </button>
            </li>
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!editable() || !isArchive()" @click="$ajax.post('file-manager/extract', {path, name: selected[0]}).then(data => show(data))">
                    <i class="ph ph-file-zip"></i> <?php echo t('Extract files'); ?>
                </button>
            </li>
        </ul>
    </div>

    <div class="fm-header" u-show="mode === 'trash'" hidden>
        <ul class="fm-header-list">
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" @click="<?php echo $list('path'); ?>">
                    <i class="ph ph-arrow-left"></i> <?php echo t('Back to files'); ?>
                </button>
            </li>
        </ul>
        <ul class="fm-header-list" u-show="!readonly">
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!selected.length" @click="$ajax.post('file-manager/restore', {names: names()}).then(data => showTrash(data))">
                    <i class="ph ph-arrow-counter-clockwise"></i> <?php echo t('Restore'); ?>
                </button>
            </li>
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!selected.length" @click="<?php echo $confirm('Delete permanently', 'Delete the selected items for good?', 'items.filter(item => selected.includes(item.key)).map(item => item.name)', "\$ajax.post('file-manager/purge', {names: names()})", 'showTrash(data)'); ?>">
                    <i class="ph ph-trash"></i> <?php echo t('Delete permanently'); ?>
                </button>
            </li>
            <li class="fm-header-item">
                <button class="btn btn--outline btn--xs" type="button" :disabled="!items.length" @click="<?php echo $confirm('Empty trash', 'Delete everything in the trash for good?', '[]', "\$ajax.post('file-manager/purge', {names: '[]'})", 'showTrash(data)'); ?>">
                    <i class="ph ph-broom"></i> <?php echo t('Empty trash'); ?>
                </button>
            </li>
        </ul>
    </div>

    <div class="fm-top">
        <div class="fm-nav">
            <button class="btn btn--icon btn--outline btn--xs" type="button" title="<?php echo t_attr('Go to parent folder'); ?>" :disabled="mode !== 'files' || path === ''" @click="<?php echo $list('parent()'); ?>">
                <i class="ph ph-arrow-bend-left-up"></i>
            </button>
            <button class="btn btn--icon btn--outline btn--xs" type="button" title="<?php echo t_attr('Back'); ?>" :disabled="cursor < 1" @click="history.back()">
                <i class="ph ph-caret-left"></i>
            </button>
            <button class="btn btn--icon btn--outline btn--xs" type="button" title="<?php echo t_attr('Next'); ?>" :disabled="cursor >= stack.length - 1" @click="history.forward()">
                <i class="ph ph-caret-right"></i>
            </button>
            <button class="btn btn--icon btn--outline btn--xs" type="button" title="<?php echo t_attr('Update'); ?>" @click="mode === 'trash' ? $ajax.post('file-manager/trash').then(data => showTrash(data)) : <?php echo $list('path'); ?>">
                <i class="ph ph-arrows-clockwise"></i>
            </button>
            <div class="fm-crumbs" u-show="mode === 'files'">
                <i class="ph ph-folder-simple-dashed"></i>
                <button class="fm-crumb" type="button" :class="drop === '/' && 'is-drop'" @click="<?php echo $list("''"); ?>"
                        @dragover.prevent="drop = '/'" @dragleave="drop = null" @drop.prevent="drop = null; <?php echo $drop("''"); ?>"><?php echo escape($root); ?></button>
                <span class="fm-crumbs-list">
                    <button class="fm-crumb" type="button" :class="drop === '/' + crumb.path && 'is-drop'" u-each="crumb in crumbs" u-text="crumb.name" @click="<?php echo $list('crumb.path'); ?>"
                            @dragover.prevent="drop = '/' + crumb.path" @dragleave="drop = null" @drop.prevent="drop = null; <?php echo $drop('crumb.path'); ?>"></button>
                </span>
            </div>
            <div class="fm-crumbs" u-show="mode === 'trash'" hidden>
                <i class="ph ph-trash"></i> <?php echo t('Trash'); ?>
            </div>
        </div>
        <div class="fm-tools">
            <div class="field field--xs field--raised" u-show="mode === 'files'">
                <label class="field-item">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="search" name="search" placeholder="<?php echo t_attr('Search in this folder'); ?>" :value="search"
                           @input.delay.400ms="search = $el.value.trim(); search.length >= 2 ? $ajax.post('file-manager/search', {path, query: search}).then(data => found(data)) : (searching && <?php echo $list('path'); ?>)">
                </label>
            </div>
            <button class="btn btn--icon btn--outline btn--xs" type="button" :title="dotfiles ? <?php echo $js('Hide hidden files'); ?> : <?php echo $js('Show hidden files'); ?>" @click="toggleDotfiles()">
                <i :class="dotfiles ? 'ph ph-eye' : 'ph ph-eye-slash'"></i>
            </button>
            <button class="btn btn--icon btn--outline btn--xs" type="button" title="<?php echo t_attr('Details'); ?>" :class="panel && 'is-active'"
                    @click="panel = !panel; panel && single() && mode === 'files' && !searching && <?php echo $details; ?>">
                <i class="ph ph-sidebar-simple"></i>
            </button>
            <button class="btn btn--icon btn--outline btn--xs" type="button" title="<?php echo t_attr('Activity'); ?>"
                    @click="$ajax.post('file-manager/log').then(({items}) => $dialog.open('tmpl-file-manager-log', {class: 'dialog--lg', title: <?php echo $js('Activity'); ?>, items}))">
                <i class="ph ph-clock-counter-clockwise"></i>
            </button>
            <button class="btn btn--icon btn--outline btn--xs" type="button" title="<?php echo t_attr('Settings'); ?>"
                    @click="$dialog.open('tmpl-file-manager-settings', {class: 'dialog--md', title: <?php echo $js('File manager settings'); ?>})">
                <i class="ph ph-gear-six"></i>
            </button>
        </div>
    </div>

    <div class="fm-main">
        <div class="fm-folders">
            <div class="fm-folder-item" :class="[mode === 'files' && path === '' && 'is-current', drop === '/' && 'is-drop'].filter(Boolean).join(' ')"
                 @click="<?php echo $list("''"); ?>" @dragover.prevent="drop = '/'" @dragleave="drop = null" @drop.prevent="drop = null; <?php echo $drop("''"); ?>">
                <i class="ph ph-hard-drives"></i> <?php echo escape($root); ?>
            </div>
            <div class="fm-tree">
                <div class="fm-folder-item" u-each="node in tree" :style="'--fm-depth: ' + node.depth"
                     :class="[mode === 'files' && path === node.path && 'is-current', drop === '/' + node.path && 'is-drop'].filter(Boolean).join(' ')"
                     @click="<?php echo $list('node.path'); ?>"
                     @dragover.prevent="drop = '/' + node.path" @dragleave="drop = null" @drop.prevent="drop = null; <?php echo $drop('node.path'); ?>">
                    <span class="fm-folder-toggle" :class="node.empty && 'is-empty'"
                          @click.stop="node.open ? collapse(node.path) : (isLoaded(node.path) ? expand(node.path) : $ajax.post('file-manager/folders', {path: node.path}).then(({folders}) => expand(node.path, folders)))">
                        <i :class="node.open ? 'ph ph-caret-down' : 'ph ph-caret-right'"></i>
                    </span>
                    <i :class="node.open ? 'ph ph-folder-open' : 'ph ph-folder'"></i>
                    <span class="fm-folder-name" u-text="node.name"></span>
                </div>
            </div>
            <div class="fm-folder-item fm-folder-trash" u-show="trash" :class="mode === 'trash' && 'is-current'" @click="$ajax.post('file-manager/trash').then(data => showTrash(data))">
                <i class="ph ph-trash"></i> <?php echo t('Trash'); ?>
            </div>
        </div>

        <div class="fm-files" u-fmselect="selectArea($event.detail)"
             @dragover.prevent="true" @drop.prevent="drop = null; editable() && $event.dataTransfer.files.length && <?php echo $upload('[...$event.dataTransfer.files]', 'path'); ?>">
            <div class="fm-files-head">
                <button class="fm-sort" type="button" @click="sort('name')">
                    <?php echo t('Name'); ?> <i u-show="sortKey === 'name'" :class="sortDir > 0 ? 'ph ph-caret-up' : 'ph ph-caret-down'"></i>
                </button>
                <button class="fm-sort" type="button" @click="sort('mtime')">
                    <span u-show="mode !== 'trash'"><?php echo t('Modified'); ?></span>
                    <span u-show="mode === 'trash'" hidden><?php echo t('Deleted'); ?></span>
                    <i u-show="sortKey === 'mtime'" :class="sortDir > 0 ? 'ph ph-caret-up' : 'ph ph-caret-down'"></i>
                </button>
                <div><?php echo t('Permissions'); ?></div>
                <button class="fm-sort" type="button" @click="sort('bytes')">
                    <?php echo t('Size'); ?> <i u-show="sortKey === 'bytes'" :class="sortDir > 0 ? 'ph ph-caret-up' : 'ph ph-caret-down'"></i>
                </button>
            </div>
            <div class="fm-files-list">
                <div class="fm-files-item" u-each="item in visible"
                     :class="[selected.includes(item.key) && 'is-selected', drop === item.key && 'is-drop'].filter(Boolean).join(' ')"
                     :data-type="item.folder ? 'folder' : 'file'" :data-key="item.key" :draggable="editable() ? 'true' : 'false'"
                     @click="select(item.key, $event); panel && selected.length === 1 && mode === 'files' && !searching && <?php echo $details; ?>"
                     @dblclick="<?php echo $open; ?>"
                     @dragstart="editable() ? drag(item, $event) : $event.preventDefault()" @dragend="dragging = []; drop = null"
                     @dragover.prevent="drop = item.folder && editable() && accepts(item) ? item.key : null" @dragleave="drop = null"
                     @drop.prevent.stop="drop = null; <?php echo $drop("item.folder && accepts(item) ? join(path, item.name) : path"); ?>">
                    <span class="fm-files-name">
                        <i :class="'ph ' + item.icon"></i>
                        <span class="fm-files-label">
                            <span u-text="item.name"></span>
                            <small u-show="searching || mode === 'trash'" u-text="'/' + item.dir"></small>
                        </span>
                    </span>
                    <span u-text="mode === 'trash' ? item.deleted : item.modified"></span>
                    <span :title="item.access" u-text="item.permissions"></span>
                    <span u-text="item.size"></span>
                </div>
            </div>
            <div class="fm-empty" u-show="!visible.length" hidden>
                <span u-show="mode === 'trash'"><?php echo t('The trash is empty.'); ?></span>
                <span u-show="mode === 'files' && searching"><?php echo t('Nothing found.'); ?></span>
                <span u-show="mode === 'files' && !searching"><?php echo t('This folder is empty.'); ?></span>
            </div>
        </div>

        <aside class="fm-details" u-show="panel" hidden>
            <div class="fm-details-empty" u-show="!details"><?php echo t('Select a file or folder to see its details.'); ?></div>
            <div class="fm-details-body" u-show="details" hidden>
                <img class="fm-details-image" u-show="details?.preview && kind(details?.name ?? '') === 'image'" :src="details?.preview && kind(details?.name ?? '') === 'image' ? previewUrl(details.name) : ''" alt>
                <div class="fm-details-title"><i :class="'ph ' + (details?.icon ?? '')"></i> <span u-text="details?.name"></span></div>
                <dl class="fm-details-list">
                    <dt><?php echo t('Type'); ?></dt>
                    <dd u-text="details?.type"></dd>
                    <dt><?php echo t('Size'); ?></dt>
                    <dd>
                        <span u-text="details?.size || ''"></span>
                        <small u-show="details?.exact" u-text="details?.exact"></small>
                        <small u-show="details?.files !== undefined" u-text="<?php echo $js('Files'); ?> + ': ' + details?.files"></small>
                        <button class="fm-link" type="button" u-show="details?.folder && details?.files === undefined" @click="$ajax.post('file-manager/size', {path, name: details.name}).then(data => sized(data))"><?php echo t('Calculate'); ?></button>
                    </dd>
                    <dt><?php echo t('Location'); ?></dt>
                    <dd><code u-text="'/' + (details?.path ?? '')"></code></dd>
                    <dt><?php echo t('Modified'); ?></dt>
                    <dd u-text="details?.modified"></dd>
                    <dt><?php echo t('Created'); ?></dt>
                    <dd u-text="details?.created"></dd>
                    <dt><?php echo t('Permissions'); ?></dt>
                    <dd>
                        <code u-text="details?.permissions"></code> <code u-text="details?.rights"></code>
                        <button class="fm-link" type="button" u-show="!readonly"
                                @click="<?php echo $ask('Change permissions', 'Permissions, e.g. 0644', 'details.permissions', "\$ajax.post('file-manager/chmod', {path, names: names(), mode: name})", "show(data); $details"); ?>"><?php echo t('Change'); ?></button>
                    </dd>
                    <dt><?php echo t('Owner'); ?></dt>
                    <dd u-text="(details?.owner ?? '') + ':' + (details?.group ?? '')"></dd>
                </dl>
                <div class="fm-details-actions">
                    <button class="btn btn--outline btn--xs" type="button" u-show="details && !details.folder" @click="(item => <?php echo $preview; ?>)(details)">
                        <i class="ph ph-eye"></i> <?php echo t('Preview'); ?>
                    </button>
                    <button class="btn btn--outline btn--xs" type="button" @click="download(JSON.stringify([details.name]))">
                        <i class="ph ph-download-simple"></i> <?php echo t('Download'); ?>
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <div class="fm-status">
        <span><?php echo t('Items'); ?>: <span u-text="status.count"></span></span>
        <span u-show="status.selected" hidden><?php echo t('Selected'); ?>: <span u-text="status.selected"></span> <span u-text="status.size ? '(' + status.size + ')' : ''"></span></span>
        <span u-show="searching && !complete" hidden><?php echo t('Only the first results are shown, refine the search.'); ?></span>
        <span u-show="clipboard" hidden><i class="ph ph-clipboard"></i> <span u-text="clipboard?.count"></span> <span u-text="clipboard?.move ? <?php echo $js('to move'); ?> : <?php echo $js('to copy'); ?>"></span></span>
        <span class="fm-status-progress" u-show="percent" hidden><progress max="100" :value="percent"></progress> <span u-text="percent + '%'"></span></span>
        <span u-show="readonly" hidden><i class="ph ph-lock-simple"></i> <?php echo t('Read-only'); ?></span>
    </div>
</div>

<template id="tmpl-file-manager-name">
    <form class="fm-dialog" @submit.prevent="entry.submit($el.elements.name.value.trim()).then(() => close(entry.id))">
        <div class="fm-dialog-body">
            <div class="field field--raised">
                <div class="field-label" u-text="entry.label"></div>
                <label class="field-item">
                    <input type="text" name="name" required :value="entry.value" @load="setTimeout(() => $el.select(), 50)">
                </label>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn btn--outline" type="button" @click="close(entry.id)"><?php echo t('Cancel'); ?></button>
            <button class="btn btn--primary" type="submit" u-text="entry.action"></button>
        </div>
    </form>
</template>

<template id="tmpl-file-manager-confirm">
    <div class="fm-dialog">
        <div class="fm-dialog-body">
            <p u-text="entry.message"></p>
            <ul class="fm-dialog-names">
                <li u-each="name in entry.names" u-text="name"></li>
            </ul>
        </div>
        <div class="dialog-footer">
            <button class="btn btn--outline" type="button" @click="close(entry.id)"><?php echo t('Cancel'); ?></button>
            <button class="btn btn--danger" type="button" @click="entry.submit().then(() => close(entry.id))" @load="setTimeout(() => $el.focus(), 50)" u-text="entry.action"></button>
        </div>
    </div>
</template>

<template id="tmpl-file-manager-conflict">
    <div class="fm-dialog">
        <div class="fm-dialog-body">
            <p><?php echo t('The target folder already has entries with these names:'); ?></p>
            <ul class="fm-dialog-names">
                <li u-each="name in entry.names" u-text="name"></li>
            </ul>
        </div>
        <div class="dialog-footer">
            <button class="btn btn--outline" type="button" @click="entry.submit('skip'); close(entry.id)"><?php echo t('Skip'); ?></button>
            <button class="btn btn--outline" type="button" @click="entry.submit('keep'); close(entry.id)"><?php echo t('Keep both'); ?></button>
            <button class="btn btn--danger" type="button" @click="entry.submit('replace'); close(entry.id)"><?php echo t('Replace'); ?></button>
        </div>
    </div>
</template>

<template id="tmpl-file-manager-preview">
    <div class="fm-preview">
        <img u-show="entry.kind === 'image'" :src="entry.kind === 'image' ? entry.url : ''" alt>
        <video u-show="entry.kind === 'video'" controls :src="entry.kind === 'video' ? entry.url : ''"></video>
        <audio u-show="entry.kind === 'audio'" controls :src="entry.kind === 'audio' ? entry.url : ''"></audio>
        <iframe u-show="entry.kind === 'pdf'" :src="entry.kind === 'pdf' ? entry.url : ''" title="PDF"></iframe>
        <pre u-show="entry.kind === 'text'" u-text="entry.text"></pre>
    </div>
</template>

<template id="tmpl-file-manager-log">
    <div class="fm-dialog-body">
        <p u-show="!entry.items.length"><?php echo t('No actions yet.'); ?></p>
        <table class="fm-log" u-show="entry.items.length">
            <thead>
                <tr>
                    <th><?php echo t('Date'); ?></th>
                    <th><?php echo t('User'); ?></th>
                    <th><?php echo t('Action'); ?></th>
                    <th><?php echo t('Files'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr u-each="row in entry.items">
                    <td u-text="row.date"></td>
                    <td u-text="row.user"></td>
                    <td u-text="row.action"></td>
                    <td><code u-text="row.target"></code></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<template id="tmpl-file-manager-settings">
    <form class="fm-dialog" @submit.prevent="$ajax.post('file-manager/settings')">
        <div class="fm-dialog-body">
            <div class="field field--raised">
                <div class="field-label"><?php echo t('Root folder'); ?></div>
                <label class="field-item">
                    <input type="text" name="root" value="<?php echo escape($settings->root); ?>" placeholder="<?php echo escape(rtrim(str_replace('\\', '/', EX_PATH), '/')); ?>">
                </label>
                <div class="field-instruction"><?php echo t('Absolute path. Leave empty for the installation folder.'); ?></div>
            </div>
            <div class="field field--raised">
                <div class="field-label"><?php echo t('Hidden paths'); ?></div>
                <label class="field-item">
                    <textarea name="hidden" rows="5"><?php echo escape(implode("\n", $settings->hidden)); ?></textarea>
                </label>
                <div class="field-instruction"><?php echo t('One per line: a path from the root or a name, wildcards like *.log work. Hidden entries cannot be opened or changed.'); ?></div>
            </div>
            <div class="field field--raised">
                <div class="field-label"><?php echo t('Forbidden extensions'); ?></div>
                <label class="field-item">
                    <input type="text" name="blocked" value="<?php echo escape(implode(', ', $settings->blocked)); ?>">
                </label>
                <div class="field-instruction"><?php echo t('Comma-separated. Such files cannot be uploaded, created, renamed to or extracted.'); ?></div>
            </div>
            <div class="field field--ui">
                <div class="field-item">
                    <input type="checkbox" name="trash" value="1"<?php echo $settings->trash ? ' checked' : ''; ?>>
                    <div class="field-label"><?php echo t('Move deleted items to the trash'); ?></div>
                </div>
                <div class="field-item">
                    <input type="checkbox" name="readonly" value="1"<?php echo $settings->readonly ? ' checked' : ''; ?>>
                    <div class="field-label"><?php echo t('Read-only mode'); ?></div>
                </div>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn btn--outline" type="button" @click="close(entry.id)"><?php echo t('Cancel'); ?></button>
            <button class="btn btn--primary" type="submit"><?php echo t('Save'); ?></button>
        </div>
    </form>
</template>
