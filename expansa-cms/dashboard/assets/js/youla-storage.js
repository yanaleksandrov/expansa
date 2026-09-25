document.addEventListener('youla:init', () => {
    class RubberBand {
        constructor(container) {
            this.container = container;
            this.el = document.createElement('div');
            this.el.className = 'u-dragselect-area';
        }
        start(x, y) {
            this.originX = x;
            this.originY = y;
            this.rect = {
                left: x,
                top: y,
                right: x,
                bottom: y
            };
            this.container.appendChild(this.el);
            this.paint();
        }
        update(x, y) {
            this.rect = {
                left: Math.min(this.originX, x),
                top: Math.min(this.originY, y),
                right: Math.max(this.originX, x),
                bottom: Math.max(this.originY, y)
            };
            this.paint();
        }
        paint() {
            this.el.style.left = `${this.rect.left}px`;
            this.el.style.top = `${this.rect.top}px`;
            this.el.style.width = `${this.rect.right - this.rect.left}px`;
            this.el.style.height = `${this.rect.bottom - this.rect.top}px`;
        }
        destroy() {
            this.el.remove();
        }
        static intersects(a, b) {
            return !(b.left > a.right || b.right < a.left || b.top > a.bottom || b.bottom < a.top);
        }
    }
    class DragSelect {
        constructor({container, selector, onSelect}) {
            this.container = container;
            this.selector = selector;
            this.onSelect = onSelect;
            this.band = null;
            this.candidates = [];
            this.hits = [];
            this.pending = null;
            this.frame = null;
            this.container.addEventListener('mousedown', this);
            this.container.addEventListener('touchstart', this, {
                passive: false
            });
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
        start(e) {
            if (e.target.closest(this.selector) || e.button !== undefined && e.button !== 0) {
                return;
            }
            this.origin = this.localPoint(this.pointerPosition(e));
            this.candidates = this.collectCandidates();
            this.hits = [];
            this.band = new RubberBand(this.container);
            this.band.start(this.origin.x, this.origin.y);
            window.addEventListener('mousemove', this);
            window.addEventListener('touchmove', this, {
                passive: false
            });
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
            this.hits = this.candidates.filter(candidate => RubberBand.intersects(rect, candidate.rect));
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
            return touch ? {
                x: touch.clientX,
                y: touch.clientY
            } : {
                x: e.clientX,
                y: e.clientY
            };
        }
        localPoint({x, y}) {
            const rect = this.container.getBoundingClientRect();
            return {
                x: x - rect.left + this.container.scrollLeft,
                y: y - rect.top + this.container.scrollTop
            };
        }
        collectCandidates() {
            return Array.from(this.container.querySelectorAll(this.selector)).map(node => {
                const box = node.getBoundingClientRect();
                const local = this.localPoint({
                    x: box.left,
                    y: box.top
                });
                return {
                    node,
                    rect: {
                        left: local.x,
                        top: local.y,
                        right: local.x + box.width,
                        bottom: local.y + box.height
                    }
                };
            });
        }
    }
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
                const ids = rawIds.map(id => Number.isNaN(+id) ? id : +id);
                component.invokeListener(attribute.expression, {
                    detail: {
                        ids
                    }
                }, el);
            }
        });
    });
    Youla.data('storage', () => ({
        items: [],
        visible: [],
        query: '',
        type: '',
        page: 1,
        hasMore: true,
        loadingMore: false,
        selected: [],
        anchor: null,
        percent: 0,
        errors: [],
        multiple: true,
        onSelect: null,
        configure(entry) {
            if (!entry) {
                return;
            }
            this.multiple = entry.multiple ?? this.multiple;
            this.type = entry.type ?? '';
            this.onSelect = typeof entry.onSelect === 'function' ? entry.onSelect : null;
        },
        recomputeVisible() {
            this.visible = this.type ? this.items.filter(item => (item.mime || '').startsWith(`${this.type}/`)) : this.items;
        },
        setType(type) {
            this.type = type;
            this.recomputeVisible();
        },
        setItems(posts, reset) {
            posts = Array.isArray(posts) ? posts : [];
            this.items = reset ? posts : this.items.concat(posts);
            this.hasMore = posts.length > 0;
            this.page = reset ? 2 : this.page + 1;
            if (reset) {
                this.selected = [];
                this.anchor = null;
            }
            this.recomputeVisible();
        },
        search(value) {
            this.query = value;
            this.page = 1;
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
                this.selected = this.isSelected(item.id) ? [] : [ item.id ];
            } else if (event?.shiftKey && this.anchor !== null) {
                this.selectRange(this.anchor, item.id);
            } else if (event?.ctrlKey || event?.metaKey) {
                this.selected = this.isSelected(item.id) ? this.selected.filter(id => id !== item.id) : [ ...this.selected, item.id ];
            } else {
                this.selected = this.isSelected(item.id) && this.selected.length === 1 ? [] : [ item.id ];
            }
            this.anchor = item.id;
        },
        selectRange(fromId, toId) {
            const from = this.visible.findIndex(item => item.id === fromId);
            const to = this.visible.findIndex(item => item.id === toId);
            if (from === -1 || to === -1) {
                this.selected = [ toId ];
                return;
            }
            const [start, end] = from < to ? [ from, to ] : [ to, from ];
            this.selected = this.visible.slice(start, end + 1).map(item => item.id);
        },
        applyDragSelection({ids}) {
            if (!this.multiple || !ids?.length) {
                return;
            }
            this.selected = [ ...new Set([ ...this.selected, ...ids ]) ];
            this.anchor = ids.at(-1);
        },
        clearSelection() {
            this.selected = [];
            this.anchor = null;
        },
        confirm() {
            if (typeof this.onSelect === 'function') {
                this.onSelect(this.multiple ? this.selectedItems() : this.selectedItems()[0]);
            }
        },
        addUploaded(posts, errors) {
            posts = Array.isArray(posts) ? posts : [];
            this.items = [ ...posts, ...this.items ];
            this.selected = posts.map(post => post.id);
            this.errors = Object.values(errors || {});
            this.recomputeVisible();
        },
        removeDeleted(ids) {
            ids = Array.isArray(ids) ? ids : [];
            this.items = this.items.filter(item => !ids.includes(item.id));
            this.selected = this.selected.filter(id => !ids.includes(id));
            this.recomputeVisible();
        }
    }));
});