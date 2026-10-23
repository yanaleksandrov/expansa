<?php
/**
 * Editor of the navigation menus: the menus, the sources of the items, the items sorted by `u-sortable`,
 * the fields of the selected item and the theme locations. The data comes from
 * App\Dashboard\Pages\NavEditor, the bindings of the markup are `u-bind` sets of `navEditor` in youla-nav-editor.js
 * and short single directives; the fields of an item are App\Models\Menu::itemFields(), a field added there
 * is shown here by its control type.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/nav-editor.php
 *
 * @var App\Models\Menu[]                      $menus
 * @var App\Models\Menu|null                   $current
 * @var array<string, string>                  $locations Labels of the theme locations by key.
 * @var array<string, string>                  $owners    Titles of the menus shown in the locations, by location.
 * @var array<int, string>                     $places    Labels of the locations of each menu, by menu ID.
 * @var string[]                               $orphans   Locations of the menu the theme no longer declares.
 * @var array<string, string>                  $types     Labels of the public post types by key.
 * @var array<string, string>                  $kinds     Labels of the items that are neither posts nor links, by type.
 * @var int                                    $clicks    Clicks of the items of the menu over the last 30 days.
 * @var array<string, App\Models\Values\Field> $fields    Fields of an item by key.
 * @var App\Dashboard\Values\MenuItem[]        $rows      Rows of the items until u-each of the editor replaces them.
 * @var string                                 $source    Key of the post type open in the sources, the first one.
 * @var string[]                               $posts     Titles of its posts until u-each of the editor replaces them.
 * @var array<string, mixed>                   $state     Menu being edited, its items as a flat list with `depth`,
 *                                                        the post types, the fields and the deepest level for the editor.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="nav-editor" u-data="navEditor({{ json_encode($state) }})">
    <aside class="nav-editor-side">
        <section class="nav-editor-section">
            <header class="nav-editor-header">
                <h6 class="nav-editor-eyebrow">{{ t('Menus') }}</h6>
                <a class="btn btn--xs btn--icon" href="{{ url('/dashboard/nav-editor?menu=new') }}" title="{{ t_attr('Create a New Menu') }}"><i class="ph ph-plus"></i></a>
            </header>
            <div class="field field--sm field--raised nav-editor-menus">
                <label class="field-item">
                    <select aria-label="{{ t_attr('Menus') }}" u-bind="menuSelect">
                        @if(! $current)
                            <option value="" selected>{{ t('Create a New Menu') }}</option>
                        @endif
                        @foreach($menus as $menu)
                            <option value="{{ url('/dashboard/nav-editor?menu=' . $menu->id) }}" data-description="{{ $places[$menu->id] ?? '' }}" @selected($menu->id === $current?->id)>{{ $menu->title }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="nav-editor-section">
            <h6 class="nav-editor-eyebrow">{{ t('Add Menu Items') }}</h6>
            <form class="nav-editor-link" u-bind="linkForm">
                <div class="field field--sm field--raised">
                    <label class="field-item">
                        <input type="text" placeholder="https://" required u-prop="link.url">
                    </label>
                </div>
                <div class="field field--sm field--raised">
                    <label class="field-item">
                        <input type="text" placeholder="{{ t_attr('Link Text') }}" u-prop="link.title">
                        <button type="submit" class="btn btn--sm btn--outline"><i class="ph ph-plus"></i></button>
                    </label>
                </div>
            </form>
            <div class="field field--sm field--raised">
                <label class="field-item">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="search" placeholder="{{ t_attr('Search') }}" u-bind="searchInput">
                </label>
            </div>
            <div class="nav-editor-posts" u-show="query" hidden>
                <p class="nav-editor-note" u-bind="noResults" hidden>{{ t('Nothing found') }}</p>
                <button type="button" class="nav-editor-post" u-bind="result">
                    <span u-text="post.title"></span>
                    <small u-bind="postType"></small>
                    <i class="ph ph-plus"></i>
                </button>
            </div>
            <div class="accordion nav-editor-sources" u-bind="sources">
                @foreach($types as $key => $label)
                    <div class="accordion-item">
                        <button type="button" class="accordion-title @if($key === $source) is-active @endif" u-bind="sourceTitle('{{ $key }}')">
                            <i class="ph ph-file-text"></i> <span>{{ $label }}</span> <i class="ph ph-caret-down"></i>
                        </button>
                        <div class="accordion-panel nav-editor-posts" u-bind="sourcePanel('{{ $key }}')"@if($key !== $source) hidden@endif>
                            <p class="nav-editor-note" u-bind="sourceEmpty('{{ $key }}')"@if($key !== $source || $posts) hidden@endif>{{ t('Nothing found') }}</p>
                            <button type="button" class="nav-editor-post" u-bind="sourcePost('{{ $key }}')">
                                <span u-text="post.title"></span>
                                <i class="ph ph-plus"></i>
                            </button>
                            @if($key === $source)
                                @foreach($posts as $title)
                                    <button type="button" class="nav-editor-post">
                                        <span>{{ $title }}</span>
                                        <i class="ph ph-plus"></i>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="accordion-item">
                    <button type="button" class="accordion-title" u-bind="sourceTitle('kinds')">
                        <i class="ph ph-shapes"></i> <span>{{ t('Special Items') }}</span> <i class="ph ph-caret-down"></i>
                    </button>
                    <div class="accordion-panel nav-editor-posts" u-bind="sourcePanel('kinds')" hidden>
                        @foreach($kinds as $type => $label)
                            <button type="button" class="nav-editor-post" u-bind="kind('{{ $type }}')">
                                <span>{{ $label }}</span>
                                <i class="ph ph-plus"></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </aside>

    <form class="nav-editor-main" id="nav-editor-form" u-bind="editor">
        <div class="field field--raised field--xl">
            <label class="field-item">
                <input type="text" value="{{ $current->title ?? '' }}" placeholder="{{ t_attr('Menu Name') }}" aria-label="{{ t_attr('Menu Name') }}" required u-prop="title">
            </label>
        </div>

        <div class="nav-editor-box">
            <header class="nav-editor-header">
                <h6 class="nav-editor-eyebrow">
                    {{ t('Menu Structure') }}
                    <span class="badge badge--xs" u-bind="count"@if(! $rows) hidden@endif>{{ count($rows) }}</span>
                    <span class="badge badge--xs badge--green-lt" title="{{ t_attr('Clicks in the last 30 days') }}" u-show="totalClicks()"@if(! $clicks) hidden@endif>
                        <i class="ph ph-cursor-click"></i> <span u-text="totalClicks()">{{ $clicks }}</span>
                    </span>
                </h6>
                <div class="nav-editor-clipboard">
                    <button type="button" class="btn btn--xs btn--icon" title="{{ t_attr('Copy Menu') }}" @click="copyMenu()"><i class="ph ph-copy"></i></button>
                    <button type="button" class="btn btn--xs btn--icon" title="{{ t_attr('Paste Menu Items') }}" @click="pasteItems()"><i class="ph ph-clipboard-text"></i></button>
                </div>
            </header>
            <div class="nav-editor-undo" u-show="removed" hidden>
                <i class="ph ph-trash"></i>
                <span>{{ t('Menu item removed') }} <strong u-text="removed?.label"></strong></span>
                <button type="button" class="btn btn--xs btn--outline" @click="undo()">{{ t('Undo') }}</button>
            </div>
            <ul class="nav-editor-list" u-bind="list">
                <li class="nav-editor-item" u-bind="row" hidden>
                    <i class="ph ph-dots-six-vertical sortable-handle"></i>
                    <button type="button" class="nav-editor-item-head" u-bind="rowHead">
                        <i u-bind="rowIcon"></i>
                        <span u-text="label(item)"></span>
                        <small class="badge badge--xs badge--red-lt" title="{{ t_attr('The post was deleted, the item is not shown on the site.') }}" u-bind="rowDeleted">{{ t('Deleted') }}</small>
                        <small class="badge badge--xs badge--orange-lt" title="{{ t_attr('The post is not published, the item is not shown on the site.') }}" u-bind="rowHidden">{{ t('Not Published') }}</small>
                        <small class="badge badge--xs" u-bind="rowType"></small>
                        <small class="badge badge--xs badge--blue-lt" u-bind="rowKind"></small>
                        <span class="nav-editor-item-clicks" title="{{ t_attr('Clicks in the last 30 days') }}" u-bind="rowClicks"><span u-text="item.clicks"></span></span>
                    </button>
                    <div class="nav-editor-item-actions">
                        <button type="button" class="btn btn--xs btn--icon nav-editor-remove" title="{{ t_attr('Remove') }}" @click="remove(i)"><i class="ph ph-trash"></i></button>
                    </div>
                </li>
                @foreach($rows as $row)
                    <li class="nav-editor-item" style="--depth: {{ $row->depth }}">
                        <i class="ph ph-dots-six-vertical sortable-handle"></i>
                        <button type="button" class="nav-editor-item-head">
                            @if($row->icon)<i class="{{ $row->icon }}"></i>@endif
                            <span>{{ $row->label }}</span>
                            @if($row->problem === 'deleted')
                                <small class="badge badge--xs badge--red-lt" title="{{ t_attr('The post was deleted, the item is not shown on the site.') }}">{{ t('Deleted') }}</small>
                            @elseif($row->problem === 'hidden')
                                <small class="badge badge--xs badge--orange-lt" title="{{ t_attr('The post is not published, the item is not shown on the site.') }}">{{ t('Not Published') }}</small>
                            @endif
                            @if($row->type !== '')
                                <small class="badge badge--xs">{{ $row->type }}</small>
                            @else
                                <small class="badge badge--xs badge--blue-lt">{{ $row->kind }}</small>
                            @endif
                            @if($row->clicks)
                                <span class="nav-editor-item-clicks" title="{{ t_attr('Clicks in the last 30 days') }}" style="--share: {{ $row->share }}"><span>{{ $row->clicks }}</span></span>
                            @endif
                        </button>
                        <div class="nav-editor-item-actions">
                            <button type="button" class="btn btn--xs btn--icon nav-editor-remove"><i class="ph ph-trash"></i></button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div u-show="! items.length"@if($rows) hidden@endif>
                <?php echo view('parts/state', ['title' => t('No Menu Items Yet'), 'description' => t('Add menu items from the column on the left.')]); ?>
            </div>
            <p class="nav-editor-note" u-show="items.length"@if(! $rows) hidden@endif>{{ t('Drag the items into the order you prefer. Click to reveal additional configuration options.') }}</p>
        </div>
    </form>

    <aside class="nav-editor-tools">
        <section class="description-list nav-editor-panel" u-bind="panel">
            <header class="nav-editor-header">
                <h6 class="description-title">{{ t('Menu Item') }}</h6>
                <button type="button" class="btn btn--xs btn--icon nav-editor-close" title="{{ t_attr('Close') }}" @click="select(-1)"><i class="ph ph-x"></i></button>
            </header>
            <dl class="description-items">
                @foreach($fields as $name => $field)
                    <div class="nav-editor-field" u-bind="field('{{ $name }}')">
                        <dt>@if($field->icon)<i class="{{ $field->icon }}"></i>@endif{{ $field->label }}</dt>
                        <dd>
                            @if($field->type === 'select' || $field->type === 'multiselect')
                                <select u-bind="control('{{ $name }}')"@if($field->type === 'multiselect') multiple@endif disabled>
                                    @foreach($field->options as $value => $text)
                                        <option value="{{ $value }}">{{ $text }}</option>
                                    @endforeach
                                </select>
                            @elseif($field->type === 'date')
                                <input type="date" u-bind="control('{{ $name }}')" disabled>
                            @elseif($field->type === 'textarea')
                                <textarea rows="2" placeholder="{{ $field->placeholder }}" u-bind="control('{{ $name }}')" disabled></textarea>
                            @else
                                <input type="text" placeholder="{{ $field->placeholder }}" u-bind="control('{{ $name }}')" disabled>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="field nav-editor-locations">
            <h6 class="nav-editor-eyebrow">{{ t('Display Location') }}</h6>
            @foreach($locations as $key => $label)
                <label class="field-item">
                    <input type="checkbox" value="{{ $key }}" u-prop="locations" @checked(in_array($key, $current->locations ?? [], true))>
                    <span class="field-label">
                        {{ $label }}
                        @if(isset($owners[$key]) && $owners[$key] !== ($current->title ?? ''))
                            <span class="field-instruction">{{ t('Currently set to: :menu', $owners[$key]) }}</span>
                        @endif
                    </span>
                </label>
            @endforeach
            @foreach($orphans as $key)
                <label class="field-item nav-editor-orphan">
                    <input type="checkbox" value="{{ $key }}" u-prop="locations" checked>
                    <span class="field-label">
                        {{ $key }}
                        <span class="field-instruction">{{ t('The current theme has no such location, the menu is not shown there.') }}</span>
                    </span>
                </label>
            @endforeach
            @if(! $locations && ! $orphans)
                <p class="nav-editor-note">{{ t('The theme has no menu locations.') }}</p>
            @endif
        </section>

        <span class="badge badge--orange-lt nav-editor-changed" u-show="isChanged()" hidden>{{ t('Unsaved changes') }}</span>
        <footer class="nav-editor-actions">
            <button class="btn btn--sm btn--primary nav-editor-save" type="submit" form="nav-editor-form" title="Ctrl+S">
                <i class="ph ph-floppy-disk"></i> {{ $current ? t('Save Menu') : t('Create Menu') }}
            </button>
            @if($current)
                <button class="btn btn--sm btn--outline t-red" type="button" u-bind="deleter">
                    <i class="ph ph-trash"></i> {{ t('Delete Menu') }}
                </button>
            @endif
        </footer>
    </aside>

    <template id="tmpl-menu-delete">
        <p class="nav-editor-confirm">{{ t('The menu and its items will be deleted permanently.') }}</p>
        <div class="dialog-footer">
            <button type="button" class="btn btn--outline" @click="$dialog.close()">{{ t('Cancel') }}</button>
            <button type="button" class="btn btn--danger" @click="$ajax.post('menus/delete', { id: entry.id }), $dialog.close()">{{ t('Delete Permanently') }}</button>
        </div>
    </template>
</div>
