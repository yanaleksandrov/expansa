document.addEventListener('youla:init', () => {
    const PREVIEWS = {
        image: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico', 'bmp'],
        video: ['mp4', 'webm'],
        audio: ['mp3', 'ogg', 'wav'],
        pdf: ['pdf'],
    };

    const STORAGE_KEY = 'fileManager.dotfiles';

    /**
     * Rubber-band selection over `.fm-files-item[data-key]`: `u-fmselect="expression"` gets
     * `$event.detail.keys` and `$event.detail.add` (Ctrl or Cmd held) when the mouse is released.
     * A click on the empty space reports no keys, which clears the selection.
     *
     * @since 2027.2
     */
    Youla.directive('fmselect', (el, output, attribute, component) => {
        if (el._x_fmselect) {
            return;
        }
        el._x_fmselect = true;

        el.addEventListener('mousedown', down => {
            if (down.button !== 0 || down.target.closest('.fm-files-item, button, input, a')) {
                return;
            }

            const band = document.createElement('div');
            band.className = 'fm-band';
            let rect = null;

            const move = event => {
                rect = {
                    left: Math.min(down.clientX, event.clientX),
                    top: Math.min(down.clientY, event.clientY),
                    right: Math.max(down.clientX, event.clientX),
                    bottom: Math.max(down.clientY, event.clientY),
                };
                if (!band.isConnected) {
                    document.body.appendChild(band);
                }
                Object.assign(band.style, {
                    left: `${rect.left}px`,
                    top: `${rect.top}px`,
                    width: `${rect.right - rect.left}px`,
                    height: `${rect.bottom - rect.top}px`,
                });
                event.preventDefault();
            };

            const up = event => {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
                band.remove();

                const keys = rect === null ? [] : [...el.querySelectorAll('.fm-files-item[data-key]')]
                    .filter(item => {
                        const box = item.getBoundingClientRect();
                        return !(box.left > rect.right || box.right < rect.left || box.top > rect.bottom || box.bottom < rect.top);
                    })
                    .map(item => item.dataset.key);

                component.invokeListener(attribute.expression, { detail: { keys, add: event.ctrlKey || event.metaKey } }, el);
            };

            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
        });
    });

    /**
     * State of the file manager page, see plugins/file-manager/views/file-manager.php.
     * `$ajax` is not available in methods, so the template makes the requests and passes the
     * answers to show(), found(), showTrash() and the other setters. Lists used by u-each are
     * array properties, not methods: u-each tracks only properties.
     *
     * @since 2027.2
     */
    Youla.data('fileManager', () => ({
        mode: 'files',
        path: '',
        items: [],
        visible: [],
        crumbs: [],
        selected: [],
        anchor: null,
        status: { count: 0, selected: 0, size: '' },

        sortKey: 'name',
        sortDir: 1,
        dotfiles: false,

        searching: false,
        complete: true,
        search: '',

        stack: [],
        cursor: -1,

        children: {},
        expanded: [''],
        tree: [],

        clipboard: null,
        dragging: [],
        drop: null,
        percent: 0,

        panel: false,
        details: null,

        readonly: false,
        trash: false,

        configure({ readonly, trash }) {
            this.readonly = readonly;
            this.trash    = trash;
            try {
                this.dotfiles = localStorage.getItem(STORAGE_KEY) === '1';
            } catch {
                this.dotfiles = false;
            }
        },

        /**
         * Show a folder listing: `how` is `push` for a visit and `pop` for the browser history.
         */
        show({ path, items, branches = {} }, how = 'push') {
            if (this.cursor === -1) {
                this.stack  = [path];
                this.cursor = 0;
                history.replaceState(history.state, '', this.url(path));
            } else if (how === 'pop') {
                const step  = this.stack[this.cursor - 1] === path ? -1 : (this.stack[this.cursor + 1] === path ? 1 : 0);
                this.cursor = step === 0 ? 0 : this.cursor + step;
                this.stack  = step === 0 ? [path] : this.stack;
            } else if (path !== this.path) {
                this.stack  = [...this.stack.slice(0, this.cursor + 1), path];
                this.cursor = this.stack.length - 1;
                history.pushState(history.state, '', this.url(path));
            }

            const same     = path === this.path && this.mode === 'files' && !this.searching;
            this.mode      = 'files';
            this.searching = false;
            this.search    = '';
            this.path      = path;
            this.items     = items.map(item => ({ ...item, key: item.name }));
            this.crumbs    = path === '' ? [] : path.split('/').map((name, i, parts) => ({ name, path: parts.slice(0, i + 1).join('/') }));

            this.children = { ...this.children, ...branches };
            this.expanded = [...new Set([...this.expanded, '', ...this.crumbs.map(crumb => crumb.path)])];
            this.rebuildTree();

            this.recompute();
            this.setSelected(same ? this.selected.filter(key => this.items.some(item => item.key === key)) : []);
            this.anchor = same ? this.anchor : null;
        },

        // Results of the search in the current folder and its subfolders.
        found({ items, complete }) {
            this.searching = true;
            this.complete  = complete;
            this.items     = items.map(item => ({ ...item, key: this.join(item.dir, item.name) }));
            this.recompute();
            this.setSelected([]);
        },

        showTrash({ items }) {
            this.mode      = 'trash';
            this.searching = false;
            this.items     = items.map(item => ({ ...item, key: item.id }));
            this.recompute();
            this.setSelected(this.selected.filter(key => this.items.some(item => item.key === key)));
        },

        url(path) {
            const url = new URL(window.location.href);
            path === '' ? url.searchParams.delete('path') : url.searchParams.set('path', path);

            return url.toString();
        },

        // Path from the address, after the browser history moves.
        locate() {
            return new URLSearchParams(window.location.search).get('path') ?? '';
        },

        recompute() {
            const key    = this.sortKey;
            const dir    = this.sortDir;
            const sorted = this.items
                .filter(item => this.dotfiles || this.mode === 'trash' || !item.name.startsWith('.'))
                .sort((a, b) => {
                    if (a.folder !== b.folder && this.mode !== 'trash') {
                        return a.folder ? -1 : 1;
                    }
                    const order = key === 'name'
                        ? a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' })
                        : (a[key] ?? 0) - (b[key] ?? 0);

                    return order * dir;
                });

            this.visible = sorted;
            this.updateStatus();
        },

        sort(key) {
            this.sortDir = this.sortKey === key ? -this.sortDir : 1;
            this.sortKey = key;
            this.recompute();
        },

        toggleDotfiles() {
            this.dotfiles = !this.dotfiles;
            try {
                localStorage.setItem(STORAGE_KEY, this.dotfiles ? '1' : '0');
            } catch {}
            this.rebuildTree();
            this.recompute();
        },

        /**
         * Select by a click as file managers do: a plain click selects one entry, Ctrl or Cmd toggles it,
         * Shift selects the range from the last clicked entry, together with Ctrl adding it to the selection.
         */
        select(key, event) {
            const keys = this.visible.map(item => item.key);
            const from = keys.indexOf(this.anchor);

            if (event.shiftKey && from !== -1) {
                const to    = keys.indexOf(key);
                const range = keys.slice(Math.min(from, to), Math.max(from, to) + 1);

                this.setSelected(event.ctrlKey || event.metaKey ? [...new Set([...this.selected, ...range])] : range);
                return;
            }

            if (event.ctrlKey || event.metaKey) {
                this.setSelected(this.selected.includes(key) ? this.selected.filter(item => item !== key) : [...this.selected, key]);
            } else {
                this.setSelected([key]);
            }
            this.anchor = key;
        },

        // Selection of the rubber band, see the `fmselect` directive.
        selectArea({ keys, add }) {
            this.setSelected(add ? [...new Set([...this.selected, ...keys])] : keys);
            this.anchor = keys[0] ?? null;
        },

        setSelected(keys) {
            this.selected = keys;
            if (keys.length !== 1 || this.details?.name !== keys[0]) {
                this.details = null;
            }
            this.updateStatus();
        },

        updateStatus() {
            const chosen = this.items.filter(item => this.selected.includes(item.key));
            const bytes  = chosen.reduce((sum, item) => sum + (item.bytes ?? 0), 0);

            this.status = { count: this.visible.length, selected: chosen.length, size: chosen.length ? this.formatBytes(bytes) : '' };
        },

        formatBytes(bytes) {
            const units  = ['B', 'KB', 'MB', 'GB', 'TB'];
            const power  = bytes > 0 ? Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1) : 0;
            const number = new Intl.NumberFormat(document.documentElement.lang || undefined, { maximumFractionDigits: power ? 1 : 0 });

            return `${number.format(bytes / 1024 ** power)} ${units[power]}`;
        },

        // Whether the selection can be changed: files of the current folder, not search results or the trash.
        editable() {
            return this.mode === 'files' && !this.searching && !this.readonly;
        },

        single() {
            return this.selected.length === 1 ? this.selected[0] : '';
        },

        isArchive() {
            return this.single().toLowerCase().endsWith('.zip');
        },

        // Path of an entry of a folder, the current one by default.
        join(path, name) {
            return path === '' ? name : `${path}/${name}`;
        },

        parent() {
            return this.path.split('/').slice(0, -1).join('/');
        },

        // Selected entries for the API, which takes them as a JSON list.
        names() {
            return JSON.stringify(this.selected);
        },

        copy(move = false) {
            this.clipboard = { from: this.path, names: this.names(), count: this.selected.length, move: move ? 1 : 0 };
        },

        // Moved entries are gone from their folder, copied ones can be pasted again.
        pasted() {
            if (this.clipboard?.move) {
                this.clipboard = null;
            }
        },

        drag(item, event) {
            if (!this.selected.includes(item.key)) {
                this.setSelected([item.key]);
            }
            this.dragging = [...this.selected];
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', this.dragging.join('\n'));
        },

        // Whether dragged entries can be dropped into a folder of the current one.
        accepts(item) {
            return item.folder && !this.dragging.includes(item.key);
        },

        // Files of a drop or an input as `files[0]`, `files[1]`... for $ajax.
        uploadPayload(files) {
            return Object.fromEntries([...files].map((file, i) => [`files[${i}]`, file]));
        },

        fileNames(files) {
            return JSON.stringify([...files].map(file => file.name));
        },

        progress(event) {
            this.percent = event && !event.end ? event.percent : 0;
        },

        rebuildTree() {
            const nodes = [];
            const walk  = (parent, depth) => {
                (this.children[parent] ?? [])
                    .filter(name => this.dotfiles || !name.startsWith('.'))
                    .forEach(name => {
                        const path = this.join(parent, name);
                        const open = this.expanded.includes(path);

                        nodes.push({ path, name, depth, open, empty: this.children[path]?.length === 0 });
                        if (open) {
                            walk(path, depth + 1);
                        }
                    });
            };
            walk('', 1);

            this.tree = nodes;
        },

        isLoaded(path) {
            return path in this.children;
        },

        expand(path, folders = null) {
            if (folders !== null) {
                this.children = { ...this.children, [path]: folders };
            }
            this.expanded = [...new Set([...this.expanded, path])];
            this.rebuildTree();
        },

        collapse(path) {
            this.expanded = this.expanded.filter(item => item !== path && !item.startsWith(`${path}/`));
            this.rebuildTree();
        },

        describe(details) {
            this.details = details;
        },

        sized({ size, files }) {
            this.details = { ...this.details, size, files };
        },

        // Kind of the preview of a file by its extension, `text` for the rest.
        kind(name) {
            const extension = name.split('.').pop().toLowerCase();

            return Object.keys(PREVIEWS).find(kind => PREVIEWS[kind].includes(extension)) ?? 'text';
        },

        endpoint(method, params) {
            return `${Youla.ajax?.baseURL ?? '/api/'}file-manager/${method}?${new URLSearchParams(params)}`;
        },

        previewUrl(name) {
            return this.endpoint('preview', { path: this.path, name });
        },

        download(names = this.names()) {
            window.location.href = this.endpoint('download', { path: this.path, names });
        },
    }));
});
