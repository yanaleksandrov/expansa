<?php
/**
 * Editor of the navigation menus: the menus, the sources of the items, the items sorted by `u-sortable`,
 * the fields of the selected item and the theme locations. The data comes from App\Dashboard\Pages\NavEditor,
 * every binding of the markup is a `u-bind` set of `navEditor` in youla-expansa.js; the fields of an item
 * are App\Models\Menu::itemFields(), a field added there is shown here by its control type.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/nav-editor.php
 *
 * @var App\Models\Menu[]                      $menus
 * @var App\Models\Menu|null                   $current
 * @var array<string, string>                  $locations Labels of the theme locations by key.
 * @var array<string, string>                  $owners    Titles of the menus shown in the locations, by location.
 * @var array<string, string>                  $types     Labels of the public post types by key.
 * @var array<string, App\Models\Values\Field> $fields    Fields of an item by key.
 * @var array<string, mixed>                   $state     Menu being edited, its items as a flat list with `depth`,
 *                                                        the post types, the fields and the deepest level for the editor.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="nav-editor" u-data="navEditor({{ json_encode($state) }})">
    <aside class="nav-editor-side">
        <header class="nav-editor-header">
            <h5>{{ t('Menus') }}</h5>
            <a class="btn btn--sm btn--icon" href="{{ url('/dashboard/nav-editor?menu=new') }}" title="{{ t_attr('Create a New Menu') }}"><i class="ph ph-plus"></i></a>
        </header>
        @if($menus)
            <nav class="nav-editor-menus">
                @foreach($menus as $menu)
                    <a class="@if($menu->id === $current?->id) is-active @endif" href="{{ url('/dashboard/nav-editor?menu=' . $menu->id) }}"><i class="ph ph-list"></i> {{ $menu->title }}</a>
                @endforeach
            </nav>
        @else
            <p class="nav-editor-note">{{ t('No menus yet') }}</p>
        @endif

        <h6 class="nav-editor-eyebrow">{{ t('Add Menu Items') }}</h6>
        <div class="field field--sm field--raised">
            <label class="field-item">
                <i class="ph ph-magnifying-glass"></i>
                <input type="search" placeholder="{{ t_attr('Search') }}" u-bind="searchInput">
            </label>
        </div>
        <form class="nav-editor-link" u-bind="linkForm">
            <div class="field field--sm field--raised">
                <label class="field-item">
                    <i class="ph ph-link"></i>
                    <input type="text" placeholder="https://" required u-bind="linkUrl">
                </label>
            </div>
            <div class="field field--sm field--raised">
                <label class="field-item">
                    <input type="text" placeholder="{{ t_attr('Link Text') }}" u-bind="linkTitle">
                </label>
            </div>
            <button type="submit" class="btn btn--sm btn--icon btn--outline" title="{{ t_attr('Add to Menu') }}"><i class="ph ph-plus"></i></button>
        </form>
        <div class="nav-editor-posts" u-bind="results" hidden>
            <button type="button" class="nav-editor-post" u-bind="result">
                <span u-bind="postTitle"></span>
                <small u-bind="postType"></small>
                <i class="ph ph-plus"></i>
            </button>
            <p class="nav-editor-note" u-bind="noResults" hidden>{{ t('Nothing found') }}</p>
        </div>
        <div class="accordion nav-editor-sources" u-bind="sources">
            @foreach($types as $key => $label)
                <div class="accordion-item">
                    <button type="button" class="accordion-title" u-bind="sourceTitle('{{ $key }}')">
                        {{ $label }} <i class="ph ph-caret-down"></i>
                    </button>
                    <div class="accordion-panel nav-editor-posts" u-bind="sourcePanel('{{ $key }}')" hidden>
                        <button type="button" class="nav-editor-post" u-bind="sourcePost('{{ $key }}')">
                            <span u-bind="postTitle"></span>
                            <i class="ph ph-plus"></i>
                        </button>
                        <p class="nav-editor-note" u-bind="sourceEmpty('{{ $key }}')" hidden>{{ t('Nothing found') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </aside>

    <form class="nav-editor-main" id="nav-editor-form" u-bind="editor">
        <h6 class="nav-editor-eyebrow">{{ t('Menu Structure') }}</h6>
        <p class="nav-editor-note">{{ t('Drag the items into the order you prefer. Click to reveal additional configuration options.') }}</p>
        <div class="nav-editor-box">
            <div class="field">
                <div class="field-label" data-required="*">{{ t('Menu Name') }}</div>
                <label class="field-item">
                    <input type="text" value="{{ $current->title ?? '' }}" required u-bind="titleInput">
                </label>
            </div>

            <ul class="nav-editor-list" u-bind="list">
                <li class="nav-editor-item" u-bind="row" hidden>
                    <i class="ph ph-dots-six-vertical sortable-handle"></i>
                    <button type="button" class="nav-editor-item-head" u-bind="rowHead">
                        <i u-bind="rowIcon"></i>
                        <span u-bind="rowLabel"></span>
                        <small u-bind="rowType"></small>
                        <small u-bind="rowLink">{{ t('Custom Link') }}</small>
                    </button>
                    <button type="button" class="btn btn--xs btn--icon nav-editor-remove" title="{{ t_attr('Remove') }}" u-bind="rowRemove"><i class="ph ph-trash"></i></button>
                </li>
            </ul>
            <div u-bind="empty"@if($state['items']) hidden@endif>
                <?php echo view('parts/state', ['title' => t('No Menu Items Yet'), 'description' => t('Add menu items from the column on the left.')]); ?>
            </div>
        </div>
    </form>

    <aside class="nav-editor-tools">
        <section class="description-list nav-editor-panel" u-bind="panel">
            <header class="nav-editor-header">
                <h5 class="description-title">{{ t('Menu Item') }}</h5>
                <button type="button" class="btn btn--xs btn--icon nav-editor-close" title="{{ t_attr('Close') }}" u-bind="closer"><i class="ph ph-x"></i></button>
            </header>
            <p class="nav-editor-note nav-editor-hint" u-bind="hint">{{ t('Select a menu item to edit it.') }}</p>
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
                    <input type="checkbox" value="{{ $key }}" u-bind="location" @checked(in_array($key, $current->locations ?? [], true))>
                    <span class="field-label">
                        {{ $label }}
                        @if(isset($owners[$key]) && $owners[$key] !== ($current->title ?? ''))
                            <span class="field-instruction">{{ t('Currently set to: :menu', $owners[$key]) }}</span>
                        @endif
                    </span>
                </label>
            @endforeach
            @if(! $locations)
                <p class="nav-editor-note">{{ t('The theme has no menu locations.') }}</p>
            @endif
        </section>

        <footer class="nav-editor-actions">
            <button class="btn btn--sm btn--primary" type="submit" form="nav-editor-form">
                <i class="ph ph-floppy-disk"></i> {{ $current ? t('Save Menu') : t('Create Menu') }}
            </button>
            @if($current)
                <button class="btn btn--sm nav-editor-delete" type="button" u-bind="deleter">
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
