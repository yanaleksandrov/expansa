// the navigation menu editor, loaded on the nav-editor page only, see SCREENS of App\Dashboard\Assets
document.addEventListener('youla:init', () => {
    /**
     * Navigation menu editor (dashboard/views/screens/nav-editor.blade.php), the markup binds to it by
     * its `u-bind` sets and short single directives. The items are a flat list in the order of the page, each with its `depth`;
     * an item moves together with its subitems and is sorted by `u-sortable`. The fields of an item come
     * from the server with their defaults and the types of items they are for, see App\Models\Menu::itemFields();
     * a field added there needs nothing here. The server turns the depths into parents, see App\Api\Menus\MenusService.
     * The state comes as the argument: `u-data="menu({{ json_encode($state) }})"`.
     *
     * @since 2027.1
     */
    Youla.data('menu', (root) => (state = {}) => {
        const maxDepth = state.maxDepth ?? 5;
        const fields   = state.fields && typeof state.fields === 'object' ? state.fields : {};
        const texts    = state.texts && typeof state.texts === 'object' ? state.texts : {};
        const kinds    = state.kinds && typeof state.kinds === 'object' ? state.kinds : {};
        const select   = state.select && typeof state.select === 'object' ? state.select : {};

        // a key tells the items apart in the page only, the proxies of the same item are not equal;
        // the ID stays with the item while the menu is edited, the clicks of the site are counted by it
        let lastKey = 0;
        const newId = () => Math.random().toString(36).slice(2, 14);
        const item = (values) => ({
            type: 'link',
            object: '',
            objectId: 0,
            ...Object.fromEntries(Object.entries(fields).map(([name, field]) => [ name, structuredClone(field.default ?? '') ])),
            original: '',
            problem: '',
            clicks: 0,
            depth: 0,
            ...values,
            id: values.id || newId(),
            key: ++lastKey,
        });

        // the title of the post, why the site leaves the item out and the clicks come from the server, they are not saved
        const serialize = (items) => JSON.stringify(items.map(({ key, original, problem, clicks, ...values }) => values));

        // what is saved: the order of the checked locations does not matter
        const snapshot = ({ title, locations, items }) => JSON.stringify([ title, [ ...locations ].sort(), serialize(items) ]);

        // stands in for the selected item while there is none: the fields of the panel are disabled and empty
        const blank = item(Object.fromEntries(Object.keys(fields).map(name => [ name, Array.isArray(fields[name].default) ? [] : '' ])));

        const data = {
            id: state.id || 0,
            title: state.title || '',
            // the checked boxes of the locations fill it on start, the locations of the state would be doubled
            locations: [],
            items: (Array.isArray(state.items) ? state.items : []).map(values => item(values)),
            types: state.types || {},
            kinds,
            maxDepth,
            // the first post type is open on load, its posts come with the state
            source: state.source || '',
            query: '',
            // null until the first search ends
            found: null,
            posts: state.posts && typeof state.posts === 'object' ? state.posts : {},
            link: { url: '', title: '' },
            // the selected item and its key; methods, not getters: only what a method reads is tracked
            active: blank,
            activeKey: 0,
            // the snapshot of the last save, taken after the items get their defaults
            saved: '',

            label(entry) {
                return entry.title || entry.original || entry.url || kinds[entry.type] || '—';
            },
            // the clicks of an item as a part of the clicks of the most clicked one
            share(entry) {
                return entry.clicks / Math.max(1, ...this.items.map(other => other.clicks || 0));
            },
            totalClicks() {
                return this.items.reduce((total, entry) => total + (entry.clicks || 0), 0);
            },
            isChanged() {
                return snapshot(this) !== this.saved;
            },
            select(index) {
                this.active    = this.items[index] || blank;
                this.activeKey = this.active.key === blank.key ? 0 : this.active.key;
                this.showDates();
            },
            // a datepicker keeps its own date, it gets the one of the selected item; a date is an ISO day
            showDates() {
                Object.keys(fields).filter(name => fields[name].type === 'date').forEach(name => {
                    const picker = document.getElementById(`nav-item-${name}`)?._x_pickadate;
                    const value  = this.active[name] || '';
                    if (!picker) {
                        return;
                    }
                    value ? picker.selectDate(new Date(`${value}T00:00`), { silent: true }) : picker.clear({ silent: true });
                });
            },
            // whether a field is for the type of the selected item, every field is shown without one
            isFor(name) {
                const types = fields[name]?.types || [];
                return !this.activeKey || types.length === 0 || types.includes(this.active.type);
            },
            // the list is rendered again after a move, the focus goes back to the selected item
            focusActive() {
                setTimeout(() => root.querySelector('.nav-editor-item.is-active .nav-editor-item-head')?.focus(), 50);
            },
            // an added item is shown without taking the focus from the source it came from
            revealActive() {
                setTimeout(() => root.querySelector('.nav-editor-item.is-active')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }), 50);
            },

            // index after the last subitem of the item
            end(index) {
                let end = index + 1;
                while (end < this.items.length && this.items[end].depth > this.items[index].depth) {
                    end++;
                }
                return end;
            },
            // index of the previous item of the same parent, -1 for the first one
            previous(index) {
                const depth = this.items[index]?.depth;
                for (let i = index - 1; i >= 0; i--) {
                    if (this.items[i].depth <= depth) {
                        return this.items[i].depth === depth ? i : -1;
                    }
                }
                return -1;
            },
            // a depth is at most one level deeper than the item above it
            normalize(items) {
                items.forEach((entry, i) => {
                    entry.depth = Math.max(0, Math.min(entry.depth, i === 0 ? 0 : items[i - 1].depth + 1, maxDepth));
                });
                this.items = items;
            },
            canMoveDown(index) {
                const next = this.end(index);
                return index >= 0 && next < this.items.length && this.items[next].depth === this.items[index].depth;
            },
            canIndent(index) {
                return this.previous(index) !== -1 && this.items[index].depth < maxDepth;
            },

            move(index, direction) {
                const items = [ ...this.items ];
                const end   = this.end(index);
                if (direction < 0) {
                    const previous = this.previous(index);
                    if (previous === -1) {
                        return;
                    }
                    items.splice(previous, 0, ...items.splice(index, end - index));
                } else {
                    if (!this.canMoveDown(index)) {
                        return;
                    }
                    const next = this.end(end);
                    items.splice(index, 0, ...items.splice(end, next - end));
                }
                this.normalize(items);
            },
            indent(index, direction) {
                if (index < 0 || (direction > 0 ? !this.canIndent(index) : this.items[index].depth === 0)) {
                    return;
                }
                const items = [ ...this.items ];
                const end   = this.end(index);
                for (let i = index; i < end; i++) {
                    items[i].depth += direction;
                }
                this.normalize(items);
            },
            // the subitems take the place of the removed item
            remove(index) {
                const items = [ ...this.items ];
                const end   = this.end(index);
                for (let i = index + 1; i < end; i++) {
                    items[i].depth--;
                }
                items.splice(index, 1);
                this.normalize(items);
                this.select(-1);
            },
            // the block moved by u-sortable: its first item, its size, its place in the list without it, its depth
            sort({ from, count, to, depth }) {
                const items = [ ...this.items ];
                const block = items.splice(from, count);
                const shift = depth - block[0].depth;

                block.forEach(entry => entry.depth += shift);
                items.splice(to, 0, ...block);
                this.normalize(items);
            },

            // a panel opens with its posts loaded, so it slides open to its full height at once;
            // the title shows the loading, a new click on it repeats the request
            openSource(key, el) {
                if (this.source === key || !(key in this.types) || this.posts[key]) {
                    this.source = this.source === key ? '' : key;
                    return;
                }
                this.$ajax.post('menus/posts', { type: key }, null, { el })
                    .then(result => {
                        this.posts = { ...this.posts, [key]: Array.isArray(result?.posts) ? result.posts : [] };
                        // the panel measures its height as it opens, so it opens after the render of the posts
                        setTimeout(() => this.source = key);
                    })
                    .catch(() => {});
            },
            // the text is looked for in every post type at once; the results stay until the next ones come,
            // a new request on the field cancels the previous one
            search(query) {
                this.query = query.trim();
                if (this.query === '') {
                    this.found = null;
                    return;
                }
                this.$ajax.post('menus/posts', { query: this.query }, null, { el: this.$el?.closest('.nav-editor-search') })
                    .then(result => {
                        this.found = Array.isArray(result?.posts) ? result.posts : [];
                    })
                    .catch(() => {});
            },
            add(values) {
                this.items = [ ...this.items, item(values) ];
                this.select(this.items.length - 1);
                this.revealActive();
            },
            addPost(key, post) {
                this.add({ type: 'post', object: key, objectId: post.id, original: post.title });
            },
            addLink() {
                const url = this.link.url.trim();
                if (url === '') {
                    return;
                }
                this.add({ url, title: this.link.title.trim() });
                this.link = { url: '', title: '' };
            },

            // the menu goes to the clipboard as JSON, its items can be pasted into another menu or site
            copyMenu() {
                const menu = { expansa: 'menu', title: this.title, items: JSON.parse(serialize(this.items)) };
                navigator.clipboard?.writeText(JSON.stringify(menu, null, 2))
                    .then(() => this.$notice.success(texts.copied))
                    .catch(() => {});
            },
            // pasted items get their own IDs, a post of another site shows as deleted after saving
            pasteItems() {
                navigator.clipboard?.readText()
                    .then(text => {
                        let menu = null;
                        try {
                            menu = JSON.parse(text);
                        } catch {}
                        if (menu?.expansa !== 'menu' || !Array.isArray(menu.items) || menu.items.length === 0) {
                            this.$notice.error(texts.pasteFailed);
                            return;
                        }
                        const pasted = menu.items
                            .filter(values => values && typeof values === 'object')
                            .map(({ id, key, ...values }) => item({ ...values, depth: +values.depth || 0 }));
                        this.normalize([ ...this.items, ...pasted ]);
                        this.select(this.items.length - pasted.length);
                        this.revealActive();
                        this.$notice.success(texts.pasted);
                    })
                    .catch(() => this.$notice.error(texts.pasteFailed));
            },

            save() {
                const current = snapshot(this);
                this.$ajax.post(this.id ? 'menus/update' : 'menus/create', {
                    id: this.id,
                    title: this.title,
                    locations: JSON.stringify(this.locations),
                    items: serialize(this.items),
                }, null, { el: root.querySelector('.nav-editor-save') }).then(() => {
                    this.saved = current;
                }).catch(() => {});
            },

            // the menus: choosing one opens it, a new one is created by the button above
            menuSelect: {
                'u-select'() {
                    return {
                        showSearch: true,
                        ...select,
                    };
                },
                '@change'() {
                    if (this.$el.value) {
                        window.location.href = this.$el.value;
                    }
                },
            },

            // the sources of the items: the search, a custom link, the posts by type
            searchInput: {
                '@input.delay.250ms'() {
                    this.search(this.$el.value);
                },
            },
            linkForm: {
                // reset() also takes away the invalid look of the emptied required field
                '@submit.prevent'() {
                    this.addLink();
                    this.$el.reset();
                },
            },
            // the rows of the markup are hidden until the script starts, the copies are shown
            result: {
                'u-each': 'post in found || []',
                ':hidden': 'false',
                '@click': 'addPost(post.type, post)',
            },
            postType: {
                'u-text': 'types[post.type] || post.type',
            },
            noResults: {
                'u-show': 'found && found.length === 0',
            },
            sourceTitle(key) {
                return {
                    '@click'() {
                        this.openSource(key, this.$el);
                    },
                    ':class'() {
                        return this.source === key && 'is-active';
                    },
                };
            },
            sourcePanel(key) {
                return {
                    'u-collapse'() {
                        return this.source === key;
                    },
                };
            },
            sourcePost(key) {
                return {
                    'u-each': `post in posts[${JSON.stringify(key)}]`,
                    ':hidden': 'false',
                    'u-text': 'post.title',
                    '@click': `addPost(${JSON.stringify(key)}, post)`,
                };
            },
            sourceEmpty(key) {
                return {
                    'u-show'() {
                        return this.posts[key]?.length === 0;
                    },
                };
            },

            // the menu and its items
            editor: {
                '@submit.prevent'() {
                    this.save();
                },
                // the shortcut of saving in editors, the form is checked as on submit
                '@keydown.window.ctrl.s.prevent'() {
                    this.$el.requestSubmit();
                },
                '@keydown.window.meta.s.prevent'() {
                    this.$el.requestSubmit();
                },
            },
            count: {
                'u-text': 'items.length',
                'u-show': 'items.length',
            },
            list: {
                'u-sortable': "{ handle: '.sortable-handle', nested: true, indent: 24, maxDepth }",
                '@sort'(event) {
                    this.sort(event.detail);
                },
            },
            // the row of the markup is hidden until the script starts, the copies are shown
            row: {
                'u-each': '(item, i) in items',
                ':hidden': 'false',
                ':style': "'--depth: ' + item.depth",
                ':class': "{ 'is-active': item.key === activeKey }",
            },
            rowHead: {
                '@click': 'select(i)',
                '@keydown.alt.up.prevent': 'select(i), move(i, -1), focusActive()',
                '@keydown.alt.down.prevent': 'select(i), move(i, 1), focusActive()',
                '@keydown.alt.left.prevent': 'select(i), indent(i, -1), focusActive()',
                '@keydown.alt.right.prevent': 'select(i), indent(i, 1), focusActive()',
            },
            rowIcon: {
                ':class': 'item.icon',
                'u-show': 'item.icon',
            },
            rowType: {
                'u-show': "item.type === 'post'",
                'u-text': 'types[item.object] || item.object',
            },
            rowKind: {
                'u-show': "item.type !== 'post'",
                'u-text': 'kinds[item.type]',
            },
            rowClicks: {
                'u-show': 'item.clicks',
                ':style': "'--share: ' + share(item)",
            },
            // the items that are not posts or links, a heading gets its label in the panel
            kind(type) {
                return {
                    '@click'() {
                        this.add({ type });
                    },
                };
            },
            rowDeleted: {
                'u-show': "item.problem === 'deleted'",
            },
            rowHidden: {
                'u-show': "item.problem === 'hidden'",
            },

            // the settings of the selected item and of the menu
            panel: {
                ':class': "activeKey && 'is-active'",
            },
            field(name) {
                return {
                    'u-show'() {
                        return this.isFor(name);
                    },
                };
            },
            // the control of a field: a select gets u-select, the title shows the title of the post while it is empty;
            // a date takes the ISO day of u-pickadate from its hidden input, the text input shows it formatted
            control(name) {
                const bindings = {
                    ':disabled': '! activeKey',
                };
                if (fields[name]?.type === 'date') {
                    bindings['@change'] = function () {
                        this.active[name] = this.$el.nextElementSibling?.value || '';
                    };
                    return bindings;
                }
                bindings['u-prop'] = `active.${name}`;
                if (this.$el.tagName === 'SELECT') {
                    bindings['u-select'] = function () {
                        return { placeholderText: fields[name]?.placeholder || '' };
                    };
                }
                if (name === 'title') {
                    bindings[':placeholder'] = function () {
                        return this.active.original || fields[name]?.placeholder || '';
                    };
                }
                return bindings;
            },
            deleter: {
                '@click'() {
                    // the label of the button is the title of the dialog
                    this.$dialog.open('tmpl-menu-delete', { id: this.id, title: this.$el.textContent.trim() });
                },
            },
        };

        data.saved = snapshot({
            title: data.title,
            locations: Array.isArray(state.locations) ? state.locations : [],
            items: data.items,
        });

        // the raw data: the component writes through its proxy into this object
        window.addEventListener('beforeunload', event => {
            if (snapshot(data) !== data.saved) {
                event.preventDefault();
            }
        });

        return data;
    });
});
