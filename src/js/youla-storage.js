document.addEventListener('youla:init', () => {
    /**
     * The rubber-band rectangle itself: a plain absolutely-positioned DOM element kept in
     * sync with two points (drag origin + current pointer), styled entirely via the
     * `.u-dragselect-area` CSS class (see `src/scss/interface/controls.scss`) rather than
     * inline styles, so it can be themed like everything else.
     *
     * @ignore
     */
    class RubberBand {
        constructor(container) {
            this.container = container;
            this.el        = document.createElement('div');
            this.el.className = 'u-dragselect-area';
        }

        start(x, y) {
            this.originX = x;
            this.originY = y;
            this.rect    = { left: x, top: y, right: x, bottom: y };

            this.container.appendChild(this.el);
            this.paint();
        }

        update(x, y) {
            this.rect = {
                left:   Math.min(this.originX, x),
                top:    Math.min(this.originY, y),
                right:  Math.max(this.originX, x),
                bottom: Math.max(this.originY, y),
            };
            this.paint();
        }

        paint() {
            this.el.style.left   = `${this.rect.left}px`;
            this.el.style.top    = `${this.rect.top}px`;
            this.el.style.width  = `${this.rect.right - this.rect.left}px`;
            this.el.style.height = `${this.rect.bottom - this.rect.top}px`;
        }

        destroy() {
            this.el.remove();
        }

        static intersects(a, b) {
            return !(b.left > a.right || b.right < a.left || b.top > a.bottom || b.bottom < a.top);
        }
    }

    /**
     * Rubber-band multi-select over a container's children matching `selector`. Reports the
     * dataset-id of every currently-covered node to `onSelect` once the gesture ends.
     *
     * Correctness/perf notes, both fixed here after they bit the first version of this file:
     * - every candidate's position is measured once, at drag start (`collectCandidates()`),
     *   converted into the container's own (possibly scrolled) local coordinate space. From
     *   there, `render()` - which runs on every animation frame while dragging - is pure
     *   rectangle arithmetic instead of forcing a fresh layout per node per mouse-move event.
     * - only `mousedown`/`touchstart` are scoped to `container`; `move`/`end` are tracked on
     *   `window` for the duration of the gesture, so releasing the pointer (or a fast drag)
     *   outside the container's bounds still ends the drag instead of leaving the rectangle
     *   stuck on screen.
     *
     * @example
     * new DragSelect({
     *     container: document.querySelector('.storage'),
     *     selector: '.storage__item',
     *     onSelect: ids => console.log(ids),
     * });
     * @ignore
     */
    class DragSelect {
        constructor({ container, selector, onSelect }) {
            this.container = container;
            this.selector  = selector;
            this.onSelect  = onSelect;

            this.band       = null;
            this.candidates = [];
            this.hits       = [];
            this.pending    = null;
            this.frame      = null;

            this.container.addEventListener('mousedown', this);
            this.container.addEventListener('touchstart', this, { passive: false });
        }

        destroy() {
            this.container.removeEventListener('mousedown', this);
            this.container.removeEventListener('touchstart', this);
            this.detachTracking();
            this.band?.destroy();
        }

        handleEvent(e) {
            switch (e.type) {
                case 'mousedown':
                case 'touchstart':
                    this.start(e);
                    break;
                case 'mousemove':
                case 'touchmove':
                    this.move(e);
                    break;
                case 'mouseup':
                case 'touchend':
                case 'touchcancel':
                    this.finish();
                    break;
            }
        }

        // Ignores gestures starting on an item itself (so a plain/ctrl/shift click there isn't
        // hijacked into a drag) and, for mouse, anything but the primary button.
        start(e) {
            if (e.target.closest(this.selector) || (e.button !== undefined && e.button !== 0)) {
                return;
            }

            this.origin     = this.localPoint(this.pointerPosition(e));
            this.candidates = this.collectCandidates();
            this.hits       = [];

            this.band = new RubberBand(this.container);
            this.band.start(this.origin.x, this.origin.y);

            window.addEventListener('mousemove', this);
            window.addEventListener('touchmove', this, { passive: false });
            window.addEventListener('mouseup', this);
            window.addEventListener('touchend', this);
            window.addEventListener('touchcancel', this);
        }

        move(e) {
            if (!this.band) {
                return;
            }
            e.preventDefault();

            this.pending = this.localPoint(this.pointerPosition(e));
            this.frame ??= requestAnimationFrame(() => this.render());
        }

        render() {
            this.frame = null;
            if (!this.band || !this.pending) {
                return;
            }

            this.band.update(this.pending.x, this.pending.y);

            const rect = this.band.rect;
            this.hits  = this.candidates.filter(candidate => RubberBand.intersects(rect, candidate.rect));

            this.candidates.forEach(candidate => {
                candidate.node.classList.toggle('active', this.hits.includes(candidate));
            });
        }

        finish() {
            this.detachTracking();

            const ids = this.hits.map(candidate => candidate.node.dataset.id).filter(Boolean);

            this.band?.destroy();
            this.band = null;
            this.hits = [];

            if (ids.length) {
                this.onSelect(ids);
            }
        }

        detachTracking() {
            window.removeEventListener('mousemove', this);
            window.removeEventListener('touchmove', this);
            window.removeEventListener('mouseup', this);
            window.removeEventListener('touchend', this);
            window.removeEventListener('touchcancel', this);

            if (this.frame) {
                cancelAnimationFrame(this.frame);
                this.frame = null;
            }
        }

        pointerPosition(e) {
            const touch = e.touches?.[0] || e.changedTouches?.[0];
            return touch ? { x: touch.clientX, y: touch.clientY } : { x: e.clientX, y: e.clientY };
        }

        // Converts viewport-relative coordinates into ones relative to the container's own
        // (possibly scrolled) content box - the space the rubber band is actually drawn in.
        localPoint({ x, y }) {
            const rect = this.container.getBoundingClientRect();
            return {
                x: x - rect.left + this.container.scrollLeft,
                y: y - rect.top + this.container.scrollTop,
            };
        }

        // Snapshot every candidate's box once, at drag start, in the same local coordinate
        // space as localPoint() - see the class docblock for why this matters.
        collectCandidates() {
            return Array.from(this.container.querySelectorAll(this.selector)).map(node => {
                const box   = node.getBoundingClientRect();
                const local = this.localPoint({ x: box.left, y: box.top });

                return {
                    node,
                    rect: { left: local.x, top: local.y, right: local.x + box.width, bottom: local.y + box.height },
                };
            });
        }
    }

    /**
     * `u-dragselect="method($event.detail)"` - rubber-band-selects over `.storage__item`
     * children and, on release, calls the given expression with `{ detail: { ids } }` (each
     * id read off the matched item's own `data-id`, coerced to a Number when it looks
     * numeric). Meant to complement, not replace, plain click/ctrl/shift selection - see
     * `Youla.data('storage', ...)` below.
     *
     * @since 1.0
     */
    Youla.directive('dragselect', (el, output, attribute, component) => {
        if (el._x_dragselect) {
            return;
        }
        el._x_dragselect = true;

        new DragSelect({
            container: el,
            selector: '.storage__item',
            onSelect: rawIds => {
                if (attribute.expression.trim() === '') {
                    return;
                }

                const ids = rawIds.map(id => (Number.isNaN(+id) ? id : +id));
                component.invokeListener(attribute.expression, { detail: { ids } }, el);
            },
        });
    });

    /**
     * A WordPress-style media library: browsable/searchable grid backed by `media/get`,
     * upload (file input + "grab from URL") backed by `media/upload` and `media/grab`,
     * deletion backed by `media/delete`, and a details side panel for the single currently
     * focused item. Click toggles selection (ctrl/cmd adds, shift selects a range, both only
     * when `multiple` is on); `u-dragselect` rubber-band selection folds into the same
     * reactive `selected` list.
     *
     * Works both as the standalone `/dashboard/media` page and, unmodified, as the content of
     * the `tmpl-media-library` dialog (see `dialogs/media-library.blade.php`) - a caller opens
     * it with `$dialog.open('tmpl-media-library', { multiple, type, onSelect(items) {...} })`
     * and this component reads that config off `entry` (the dialog stack entry it was mounted
     * inside of) via `@load="configure(entry)"`.
     *
     * Two things every method here has to respect:
     *
     * - `$ajax`/`$notice`/`$dialog` never appear in a method body. Those magic helpers only
     *   resolve inside a template expression's own evaluation, not inside a plain method here
     *   (see e.g. `files-uploader.php`'s inline `$ajax.post(...).then(...)`). So every network
     *   call lives in the markup (`parts/media-library.blade.php`), which calls back into
     *   these plain state-mutating methods with the response.
     * - `u-each` tracks its list dependency by taking the *literal leading identifier* of its
     *   expression (a regex over the source text, not real dependency tracking - see
     *   `Component.computeOutput()`'s `directive === "u-each"` branch in youla.js). A method
     *   call there (`u-each="... in filtered()"`) resolves to a dependency named `"filtered"`,
     *   which never matches an actual reactive mutation, so the grid would silently stop
     *   updating after the very first render. `visible` is kept as a plain array *property*
     *   for exactly this reason - every mutator below that can change what should be on
     *   screen ends with `this.recomputeVisible()`, and the template loops over `visible`
     *   itself, never a method call.
     *
     * @since 1.0
     */
    Youla.data('storage', () => ({
        // Listing state.
        items: [],
        visible: [],
        query: '',
        type: '',
        page: 1,
        hasMore: true,
        loadingMore: false,

        // Selection state.
        selected: [],
        anchor: null,

        // Upload/error feedback.
        percent: 0,
        errors: [],

        // Picker configuration - only set once this component is mounted inside a dialog
        // stack entry (see the class docblock above). Standalone page usage (media.blade.php)
        // never calls this, so `multiple`/`onSelect` simply keep their page-mode defaults.
        multiple: true,
        onSelect: null,

        configure(entry) {
            if (!entry) {
                return;
            }
            this.multiple = entry.multiple ?? this.multiple;
            this.type     = entry.type ?? '';
            this.onSelect = typeof entry.onSelect === 'function' ? entry.onSelect : null;
        },

        // The only place `visible` is written - every method below that changes `items` or
        // `type` ends with this call. See the class docblock for why `visible` has to be a
        // plain property rather than a `filtered()` method.
        recomputeVisible() {
            this.visible = this.type
                ? this.items.filter(item => (item.mime || '').startsWith(`${this.type}/`))
                : this.items;
        },

        setType(type) {
            this.type = type;
            this.recomputeVisible();
        },

        // Called with the `media/get` response - `reset` on the first page of a fresh query,
        // otherwise appended (pagination via `@intersect` on the grid's bottom sentinel).
        setItems(posts, reset) {
            posts = Array.isArray(posts) ? posts : [];

            this.items   = reset ? posts : this.items.concat(posts);
            this.hasMore = posts.length > 0;
            this.page    = reset ? 2 : this.page + 1;
            if (reset) {
                this.selected = [];
                this.anchor   = null;
            }

            this.recomputeVisible();
        },

        search(value) {
            this.query   = value;
            this.page    = 1;
            this.hasMore = true;
        },

        itemById(id) {
            return this.items.find(item => item.id === id);
        },

        isSelected(id) {
            return this.selected.includes(id);
        },

        editing() {
            return this.selected.length === 1 ? this.itemById(this.selected[0]) : null;
        },

        selectedItems() {
            return this.items.filter(item => this.isSelected(item.id));
        },

        toggle(item, event) {
            if (!this.multiple) {
                this.selected = this.isSelected(item.id) ? [] : [item.id];
            } else if (event?.shiftKey && this.anchor !== null) {
                this.selectRange(this.anchor, item.id);
            } else if (event?.ctrlKey || event?.metaKey) {
                this.selected = this.isSelected(item.id)
                    ? this.selected.filter(id => id !== item.id)
                    : [...this.selected, item.id];
            } else {
                this.selected = this.isSelected(item.id) && this.selected.length === 1 ? [] : [item.id];
            }
            this.anchor = item.id;
        },

        selectRange(fromId, toId) {
            const from = this.visible.findIndex(item => item.id === fromId);
            const to   = this.visible.findIndex(item => item.id === toId);
            if (from === -1 || to === -1) {
                this.selected = [toId];
                return;
            }

            const [start, end] = from < to ? [from, to] : [to, from];
            this.selected = this.visible.slice(start, end + 1).map(item => item.id);
        },

        // Bridges `u-dragselect`'s plain id-list output into the same reactive model click
        // selection uses above.
        applyDragSelection({ ids }) {
            if (!this.multiple || !ids?.length) {
                return;
            }
            this.selected = [...new Set([...this.selected, ...ids])];
            this.anchor   = ids.at(-1);
        },

        clearSelection() {
            this.selected = [];
            this.anchor   = null;
        },

        confirm() {
            if (typeof this.onSelect === 'function') {
                this.onSelect(this.multiple ? this.selectedItems() : this.selectedItems()[0]);
            }
        },

        // Called with a `media/upload` or `media/grab` response - newly added files land first
        // and become the selection, so a picker's "Select" button immediately targets them.
        addUploaded(posts, errors) {
            posts = Array.isArray(posts) ? posts : [];

            this.items    = [...posts, ...this.items];
            this.selected = posts.map(post => post.id);
            this.errors   = Object.values(errors || {});

            this.recomputeVisible();
        },

        // Called with a `media/delete` response's `deleted` id list.
        removeDeleted(ids) {
            ids = Array.isArray(ids) ? ids : [];

            this.items    = this.items.filter(item => !ids.includes(item.id));
            this.selected = this.selected.filter(id => !ids.includes(id));

            this.recomputeVisible();
        },
    }));
});
