document.addEventListener('youla:init', ()=> {
    // settings of youla-ajax.js from DashboardAssets: API address, CSRF names, error markup, texts
    Youla.ajax = { ...window.youla?.ajax, ...Youla.ajax };

    /**
     * Multi-step wizard: `u-step="condition"` marks a panel's completion; use as `u-data="step"`.
     * `u-step.required` also requires every required field's native `checkValidity()`.
     * `u-step:action="expr"` runs once each time that panel becomes the current one.
     *
     * @since 1.0
     */
    (() => {
        // Local, not dom.js's closestDirective() — this plugin only reaches into the public "u-data"/".__x" contract.
        function hasUData(el) {
            return [...el.attributes].some(({ name }) => name === 'u-data' || name.startsWith('u-data.'));
        }

        function closestComponent(el) {
            while (el && !hasUData(el)) {
                el = el.parentElement;
            }
            return el ? el.__x : null;
        }

        const REQUIRED_FIELDS_SELECTOR = 'input[required], select[required], textarea[required]';

        Youla.directive('step', (el, output, attribute, component) => {
            const wizard   = component.data;
            const step     = wizard.getStep(el);
            const required = attribute.modifiers.includes('required');

            // Bound once per panel; refresh(el) recomputes only this panel's checkValidity(), not the whole component.
            if (required && !el._x_stepRequiredBound) {
                el._x_stepRequiredBound = true;

                el.querySelectorAll(REQUIRED_FIELDS_SELECTOR).forEach(field => {
                    ['input', 'change'].forEach(event => field.addEventListener(event, () => component.refresh(el)));
                });
            }

            // Bare "u-step.required" (no expression) means the required-fields check alone is the condition, not extra on top of an absent (false) one.
            let isComplete = required && attribute.expression.trim() === '' ? true : !!output;

            if (required) {
                isComplete = isComplete && [...el.querySelectorAll(REQUIRED_FIELDS_SELECTOR)].every(field => field.checkValidity());
            }

            // "step" is a reference into the reactive "steps" array (see getStep()), so this write triggers a normal refresh on its own.
            if (step.isComplete !== isComplete) {
                step.isComplete = isComplete;
            }
        });

        Youla.data('step', () => ({
            steps: [],
            currentIndex: 1,
            progress() {
                const total   = this.steps.length;
                const current = Math.min(this.currentIndex, total);

                let complete = 0;
                for(let index = 0; index < current; index++) {
                    if(this.steps[index].isComplete) {
                        complete++;
                    }
                }
                return {
                    total, complete, current,
                    incomplete: total - complete,
                    progress: `${Math.floor(current / total * 100)}%`,
                    completion: `${Math.floor(complete / total * 100)}%`,
                    percentage: Math.floor(complete / total * 100),
                };
            },
            // "index" is 1-based, matching every other public step number; the "steps" array itself stays 0-based.
            stepAt(index) {
                return this.steps[index - 1] || { el: null };
            },
            current() {
                return this.stepAt(this.currentIndex);
            },
            previous() {
                return this.stepAt(this.previousIndex());
            },
            next() {
                return this.stepAt(this.nextIndex());
            },
            previousIndex() {
                return this.currentIndex - 1 >= 1 ? this.currentIndex - 1 : null;
            },
            nextIndex() {
                return this.currentIndex + 1 <= this.steps.length ? this.currentIndex + 1 : null;
            },
            isStep(index) {
                return Array.isArray(index) ? index.includes(this.currentIndex) : index === this.currentIndex;
            },
            // Each argument is either an exact index or a [from, to] inclusive range, e.g. isSteps(1, [3, 5]).
            isSteps(...values) {
                return values.some(value => (
                    Array.isArray(value)
                        ? this.currentIndex >= value[0] && this.currentIndex <= value[1]
                        : value === this.currentIndex
                ));
            },
            isFirst() {
                return this.previousIndex() === null;
            },
            isNotFirst() {
                return !this.isFirst();
            },
            isLast() {
                return this.nextIndex() === null;
            },
            isNotLast() {
                return !this.isLast();
            },
            isCompleted() {
                return this.current().isComplete && this.nextIndex() === null;
            },
            isUncompleted() {
                return !this.isCompleted();
            },
            canGoNext() {
                return this.current().isComplete && this.nextIndex() !== null;
            },
            cannotGoNext() {
                return !this.canGoNext();
            },
            canGoBack() {
                return this.previousIndex() !== null;
            },
            cannotGoBack() {
                return !this.canGoBack();
            },
            getState() {
                return {
                    currentIndex: this.currentIndex,
                    isFirst: this.isFirst(),
                    isNotFirst: this.isNotFirst(),
                    isLast: this.isLast(),
                    isNotLast: this.isNotLast(),
                    canGoBack: this.canGoBack(),
                    cannotGoBack: this.cannotGoBack(),
                    canGoNext: this.canGoNext(),
                    cannotGoNext: this.cannotGoNext(),
                    isCompleted: this.isCompleted(),
                    isUncompleted: this.isUncompleted(),
                    progress: this.progress(),
                };
            },
            goNext() {
                this.goto(this.nextIndex());
            },
            goBack() {
                this.goto(this.previousIndex());
            },
            goto(index) {
                const previousIndex = this.currentIndex;

                if(index !== null && this.steps[index - 1] !== void 0) {
                    this.currentIndex = index;
                }
                this.render();

                if (this.currentIndex !== previousIndex) {
                    this.runAction(this.steps[this.currentIndex - 1]);
                }
                return this.current();
            },
            // Read straight off the DOM, not as a reactive attribute: "u-step:action" has no registered directive, so core skips it entirely.
            runAction(step) {
                const expression = step?.el.getAttribute('u-step:action');
                if (!expression) {
                    return;
                }

                const component = closestComponent(step.el);
                component?.invokeListener(expression, null, step.el);
            },
            render() {
                this.steps.forEach((step, index) => {
                    const isHidden = (index + 1) !== this.currentIndex;
                    if(step.el.hidden !== isHidden) {
                        step.el.hidden = isHidden;
                    }
                });
            },
            // Returns a live reference into the reactive "steps" array, not a bare object outside it.
            getStep(el) {
                let index = el._x_stepIndex;
                if (index === undefined) {
                    index = el._x_stepIndex = this.steps.push({ el, isComplete: true }) - 1;
                    this.render();
                }
                return this.steps[index];
            },
        }));
    })();

    /**
     * Notifications system: a single `u-data="notice"` container (parts/footer.html) holds the
     * queue. `$notice` resolves to that container's data, so `$notice.info('Saved')` works anywhere.
     *
     * @since 1.0
     */
    (() => {
        Youla.variable('notice', () => document.querySelector('[u-data="notice"]')?.__x?.data);

        Youla.data('notice', () => ({
            items: [],
            duration: 7000,
            hovering: false,
            info( message, duration ) {
                this.add( message, 'info', duration );
            },
            success( message, duration ) {
                this.add( message, 'success', duration );
            },
            warning( message, duration ) {
                this.add( message, 'warning', duration );
            },
            error( message, duration ) {
                this.add( message, 'error', duration );
            },
            loading( message, duration ) {
                this.add( message, 'loading', duration );
            },
            // @mouseenter on the container: freezes every item's countdown where it stood.
            pause() {
                this.hovering = true;

                this.items.forEach(item => {
                    if ( item.timer ) {
                        clearTimeout( item.timer );
                        item.timer     = null;
                        item.remaining = Math.max( 0, item.remaining - ( Date.now() - item.startedAt ) );
                    }
                });
            },
            // @mouseleave: picks every countdown back up from where pause() froze it.
            resume() {
                this.hovering = false;

                this.items.forEach( item => this.schedule(item.id) );
            },
            schedule( id ) {
                let item = this.items.find( item => item.id === id );
                if ( item && !item.timer && item.duration ) {
                    item.startedAt = Date.now();
                    item.timer     = setTimeout( () => this.close(id), item.remaining );
                }
            },
            elapsed( item ) {
                return ( item.duration - item.remaining ) + ( item.timer ? Date.now() - item.startedAt : 0 );
            },
            close( id ) {
                let item = this.items.find( item => item.id === id );
                if ( typeof item !== 'undefined' ) {
                    clearTimeout( item.timer );

                    // u-each only re-renders when "items" itself is reassigned, not on a mutated nested key.
                    this.items = this.items.map( item => item.id === id ? { ...item, selectors: [ ...item.selectors, 'hiding' ] } : item );

                    setTimeout( () => {
                        this.items = this.items.filter( item => item.id !== id );
                    }, 1000 )
                }
            },
            add( message, type, duration ) {
                if ( message ) {
                    let id = Date.now();

                    if ( duration === 'auto' ) {
                        duration = Math.max( message.length * 70, 1500 );
                    } else if ( duration === void 0 ) {
                        duration = this.duration;
                    }

                    // Spinner is a real inline <svg> (parts/footer.html), animated via CSS, so it can be paused on :hover.
                    this.items = [ ...this.items, {
                        id: id,
                        message: message,
                        closable: true,
                        selectors: [ type || 'info' ],
                        duration: duration,
                        remaining: duration,
                        startedAt: Date.now(),
                        timer: null,
                        classes() {
                            return this.selectors.map( x => 'is-' + x ).join(' ')
                        },
                    } ];

                    if ( !this.hovering ) {
                        this.schedule(id);
                    }
                }
            },
        }));
    })();

    /**
     * Accessible, stackable dialog: open() pushes a new one on top instead of replacing the
     * current one, so a template can raise its own (e.g. a confirm) from within another. Only
     * the outermost dialog syncs to "?dialog=" in the URL — nested ones are transient.
     *
     * @since 1.0
     */
    (() => {
        Youla.variable('dialog', () => document.querySelector('[u-data="dialog"]')?.__x?.data);

        const searchParamsHandler = (param, value, isRemove) => {
            const url    = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            if (isRemove) {
                params.delete(param);
            } else {
                params.set(param, value);
            }
            url.search = params.toString();

            window.history.replaceState({}, '', url.toString());
        };

        let uid     = 0;
        let scrollY = 0;

        /**
         * Freezes body at its current scroll offset — plain overflow:hidden alone doesn't
         * block touch scrolling on iOS Safari.
         */
        function lockScroll() {
            scrollY = window.scrollY;

            Object.assign(document.body.style, {
                position: 'fixed',
                top: `-${scrollY}px`,
                width: '100%',
                overflow: 'hidden',
            });
        }

        /**
         * Restores body scroll to the offset lockScroll() froze it at.
         */
        function unlockScroll() {
            Object.assign(document.body.style, {
                position: '',
                top: '',
                width: '',
                overflow: '',
            });

            // "instant" overrides the site's global "scroll-behavior: smooth" — this restore isn't a user-facing scroll.
            window.scrollTo({ top: scrollY, left: 0, behavior: 'instant' });
        }

        // injectDataProviders() reruns this factory per u-data element; "root" only matters for this one's own component.
        Youla.data('dialog', (root) => ({
            stack: [],

            open(templateID, data = {}) {
                setTimeout(() => {
                    let template = document.getElementById(templateID);
                    if (!template) {
                        return;
                    }

                    const isBase = this.stack.length === 0;

                    // "id"/"content" go last so they always win: a caller's own data can freely
                    // include a same-named field (e.g. opening with a Post - which always has its
                    // own "id" and "content" - would otherwise silently clobber this entry's real
                    // id and, worse, wipe out the dialog's actual markup with the post's own,
                    // usually-empty "content" field).
                    // u-each (parts/footer.html) initializes each entry itself, wiring up the template's own "@click".
                    this.stack.push({ ...data, id: ++uid, content: template.innerHTML });

                    // Only the base dialog locks scroll — a nested call would read scrollY as 0 (already frozen).
                    if (isBase) {
                        lockScroll();
                    }

                    root.dispatchEvent(new Event('open', { bubbles: true }));

                    if (isBase) {
                        searchParamsHandler('dialog', templateID, false);
                    }
                }, 25);
            },
            // No "id" closes the top dialog; an explicit one (the backdrop's own click) closes that entry.
            close(id) {
                const target = id ?? this.stack.at(-1)?.id;
                this.stack   = this.stack.filter(dialog => dialog.id !== target);

                root.dispatchEvent(new Event('close', { bubbles: true }));

                if (this.stack.length === 0) {
                    unlockScroll();
                    searchParamsHandler('dialog', null, true);
                }
            },
            // Snapshot first: close() reassigns "stack" on every call, so iterating the live array would skip entries.
            clear() {
                [...this.stack].forEach(entry => this.close(entry.id));
            },
            // Reopens the base dialog from a shared URL, e.g. via "@load" on the element matching templateID.
            async init(templateID, callback) {
                const params = new URLSearchParams(window.location.search);

                if (templateID && params.get('dialog') === templateID && callback) {
                    const data = await callback();

                    if (data) {
                        this.open(templateID, data);
                    }
                }
            },
        }));
    })();

    /**
     * Password policy: checks a string against a fixed policy (minimum count per character
     * class, minimum length) and can generate a password satisfying it.
     *
     * @since 1.0
     */
    Youla.data('password', () => ({
        value: '',
        visible: false,
        progress: 0,
        labels: ['Слишком слабый', 'Слабый', 'Средний', 'Хороший', 'Отличный'],
        min: {
            lowercase: 2,
            uppercase: 2,
            special: 2,
            digit: 2,
            length: 12
        },
        valid: {
            lowercase: false,
            uppercase: false,
            special: false,
            digit: false,
            length: false
        },
        charsets: {
            lowercase: 'abcdefghijklmnopqrstuvwxyz',
            uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            special: '!@#$%^&*(){|}~',
            digit: '0123456789'
        },
        toggle() {
            this.visible = !this.visible;
        },
        level() {
            return Math.min(4, Math.round(this.progress / 25));
        },
        label() {
            return this.labels[this.level()];
        },
        check(value) {
            // Whitespace isn't part of any charset and would otherwise count toward length for free.
            if (/\s/.test(value)) {
                value = this.value = value.replace(/\s/g, '');
            }

            let matchCount = 0;
            // One point per character class plus one for the length rule.
            let totalWeight = Object.keys(this.charsets).reduce((sum, type) => sum + this.min[type], 0) + 1;

            for (const type in this.charsets) {
                let charsetRegex = new RegExp(`[${this.charsets[type]}]`, 'g');
                let charsetCount = (value.match(charsetRegex) || []).length;

                matchCount += Math.min(charsetCount, this.min[type]);
                this.valid[type] = charsetCount >= this.min[type];
            }

            this.valid.length = value.length >= this.min.length;
            if (this.valid.length) {
                matchCount += 1;
            }

            this.progress = (matchCount / totalWeight) * 100;

            return this.progress;
        },
        generate() {
            let pool = Object.values(this.charsets).join('');
            let password = '';

            for (const type in this.charsets) {
                for (let i = 0; i < this.min[type]; i++) {
                    password += this.charsets[type][Math.floor(Math.random() * this.charsets[type].length)];
                }
            }

            while (password.length < this.min.length) {
                password += pool[Math.floor(Math.random() * pool.length)];
            }

            this.value = this.shuffle(password);
            this.check(this.value);

            return this.value;
        },
        shuffle(password) {
            let array = password.split('');

            for (let i = array.length - 1; i > 0; i--) {
                let j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }

            return array.join('');
        },
    }));

    /**
     * Avatar uploader.
     *
     * @since 1.0
     */
    Youla.data('avatar', () => ({
        name: '',
        image: '',
        field: {
            'u-prop': 'name',
        },
        picture: {
            ':title': 'name',
            ':style': "image && `background-image:url(${image})`",
        },
        initials: {
            'u-show': '!image',
            'u-text'() {
                return this.name.trim().split(/\s+/).map(word => word[0]).slice(0, 2).join('').toUpperCase();
            },
        },
        uploader: {
            '@change'() {
                let file = this.$event.target.files[0];
                if (file) {
                    let reader = new FileReader();
                    reader.onload = e => this.image = e.target.result;
                    reader.readAsDataURL(file);
                }
            },
        },
        remover: {
            'u-show': 'image',
            '@click'() {
                let input = this.$root.querySelector('input[type="file"]');
                if (input) {
                    input.value = '';
                }
                this.image = '';
            },
        },
    }));

    /**
     * Table checkboxes and filter: rows "field is/is not values" from `data-fields` (key => {label, icon, options})
     * and `data-rows`; every change of a filter with values reloads the rows through the API and the page URL.
     *
     * @since 1.0
     */
    Youla.data('table', (root) => {
        const parse = (json, fallback) => {
            try {
                return JSON.parse(json || '') ?? fallback;
            } catch {
                return fallback;
            }
        };

        const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[char]);

        return {
            fields: parse(root.dataset.fields, {}),
            rows: parse(root.dataset.rows, []),
            // the text of the search field, applied with the filters and shown as a chip
            search: new URLSearchParams(window.location.search).get('s') || '',
            // index of the filter whose chip opened the values popover
            editing: null,
            anchor: null,
            bulk: 0,
            // $ajax of the table, set by its `@load`: the methods called from the listeners don't get it
            ajax: null,
            trigger: {
                '@change': 'selectAll($el, $root)',
            },
            item: {
                '@click': 'selectItem($el, $root, $event)',
            },
            items(root) {
                return [...root.querySelectorAll('[u-bind~="item"]')];
            },
            count(root) {
                this.bulk = this.items(root).filter(input => input.checked).length;
            },
            selectAll(el, root) {
                this.items(root).forEach(input => input.checked = el.checked);
                this.count(root);
            },
            selectItem(el, root, event) {
                let items   = this.items(root);
                let index   = items.indexOf(el);
                let checked = el.checked;
                let start   = event.shiftKey && this.anchor !== null ? this.anchor : index;

                for (let i = Math.min(start, index); i <= Math.max(start, index); i++) {
                    items[i].checked = checked;
                }
                this.anchor = index;
                this.count(root);
            },
            resetAll(root) {
                root.querySelectorAll('[u-bind~="trigger"], [u-bind~="item"]').forEach(input => input.checked = false);
                this.anchor = null;
                this.bulk   = 0;
            },
            activeFilters() {
                return this.rows.filter(row => row.values.length);
            },
            isFirstActive(index) {
                return this.rows.findIndex(row => row.values.length) === index;
            },
            addFilter(field) {
                this.rows.push({ field, operator: 'is', values: [] });
            },
            removeFilter(index) {
                const [row] = this.rows.splice(index, 1);
                if (row.values.length) {
                    this.applyFilters();
                }
            },
            clearFilters() {
                const applied = this.activeFilters().length || this.search;
                this.rows     = [];
                this.search   = '';
                if (applied) {
                    this.applyFilters();
                }
            },
            clearSearch() {
                this.search = '';
                this.applyFilters();
            },
            // The values of the previous field are not options of the new one.
            setFilterField(index, field) {
                const row = this.rows[index];
                if (row.field === field) {
                    return;
                }
                const applied = row.values.length;
                row.field     = field;
                row.values    = [];
                if (applied) {
                    this.applyFilters();
                }
            },
            setFilterOperator(index, operator) {
                const row = this.rows[index];
                if (row.operator === operator) {
                    return;
                }
                row.operator = operator;
                if (row.values.length) {
                    this.applyFilters();
                }
            },
            setFilterValues(index, select) {
                this.rows[index].values = Array.from(select.selectedOptions, option => option.value);
                this.applyFilters();
            },
            // Rendered via u-html before :value in the tag, as in `builder`: options must exist before the value is selected.
            filterOptions(row) {
                const options = this.fields[row.field]?.options || {};
                return Object.entries(options)
                    .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
                    .join('');
            },
            filterChoices(index) {
                const row     = this.rows[index];
                const options = this.fields[row?.field]?.options || {};
                return Object.entries(options)
                    .map(([value, label]) => `<label class="table-filter-choice"><input type="checkbox" value="${escapeHtml(value)}"${row.values.includes(value) ? ' checked' : ''}> ${escapeHtml(label)}</label>`)
                    .join('');
            },
            // A filter without values has no chip, so its popover would lose the anchor.
            toggleFilterValue(input) {
                const row = this.rows[this.editing];
                if (!row || input.type !== 'checkbox') {
                    return;
                }
                row.values = input.checked ? [ ...row.values, input.value ] : row.values.filter(value => value !== input.value);
                if (!row.values.length) {
                    input.closest('[popover]')?.hidePopover();
                }
                this.applyFilters();
            },
            filterLabels(row, selected) {
                const options = this.fields[row.field]?.options || {};
                return row.values.length > 2
                    ? selected.replace(':count', row.values.length)
                    : row.values.map(value => options[value] ?? value).join(', ');
            },
            applyFilters() {
                const url = new URL(window.location.href);

                [ ...url.searchParams.keys() ]
                    .filter(key => key.startsWith('filter['))
                    .forEach(key => url.searchParams.delete(key));

                const search = this.search.trim();
                search ? url.searchParams.set('s', search) : url.searchParams.delete('s');

                // the table is built by the API from the same query as the page: the part, the search and the order
                const payload = Object.fromEntries(url.searchParams);

                this.activeFilters().forEach((row, i) => {
                    url.searchParams.set(`filter[${i}][field]`, row.field);
                    url.searchParams.set(`filter[${i}][operator]`, row.operator);
                    payload[`filter[${i}][field]`]    = row.field;
                    payload[`filter[${i}][operator]`] = row.operator;
                    row.values.forEach((value, j) => {
                        url.searchParams.append(`filter[${i}][values][]`, value);
                        payload[`filter[${i}][values][${j}]`] = value;
                    });
                });

                window.history.replaceState(null, '', url);

                // the API replaces the rows: their bindings to this component are initialized again
                this.ajax.post('tables/filter', payload).then(() => {
                    this.resetAll(root);
                    root.__x.initialize(root.querySelector('.table-body'));
                });
            },
        };
    });

    /**
     * Custom fields builder - the whole Custom Fields group editor page
     * (dashboard/views/field-groups.blade.php): title/status, its location rule groups
     * (ORed together, rules within a group ANDed - see `form/builder.blade.php`, embedded
     * as a plain markup partial rather than its own component) and its field rows.
     *
     * @since 1.0
     */
    Youla.data('builder', (root) => {
        let state;
        try {
            state = JSON.parse(root.dataset.state || '{}');
        } catch {
            state = {};
        }

        let valueOptions = {};
        try {
            valueOptions = JSON.parse(root.dataset.valueOptions || '{}');
        } catch {
            valueOptions = {};
        }

        const defaultRule = () => ({
            location: Object.keys(valueOptions)[0] || 'post_type',
            operator: '==',
            value: '',
        });

        const TEXT_LIKE_TYPES = [ 'text', 'password', 'number' ];
        const CHOICE_TYPES = [ 'select', 'checkbox', 'radio' ];

        const blankField = (type) => ({
            type,
            label: '',
            name: '',
            required: false,
            instruction: '',
            placeholder: '',
            min: '',
            max: '',
            rows: '',
            options: '',
        });

        const slugifyName = (label) => String(label || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');

        const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[char]);

        return {
            id: state.id || 0,
            title: state.title || '',
            status: state.status || 'active',
            valueOptions,
            groups: (Array.isArray(state.groups) && state.groups.length) ? state.groups : [ { rules: [ defaultRule() ] } ],
            fields: (Array.isArray(state.fields) ? state.fields : []).map((field) => ({
                ...blankField(field.type || 'text'),
                ...field,
                required: !!(field.attributes && field.attributes.required),
                placeholder: field.attributes?.placeholder || '',
                min: field.attributes?.min ?? '',
                max: field.attributes?.max ?? '',
                rows: field.attributes?.rows ?? '',
                options: Object.entries(field.options || {})
                    .map(([value, label]) => (value === label ? value : `${value}:${label}`))
                    .join('\n'),
            })),

            addGroup() {
                this.groups.push({ rules: [ defaultRule() ] });
            },
            removeGroup(index) {
                this.groups.splice(index, 1);
            },
            addRule(key) {
                this.groups[key].rules.push(defaultRule());
            },
            removeRule(key, index) {
                this.groups[key].rules.splice(index, 1);
            },
            // Rendered via u-html (before u-prop in the tag, so it runs first) rather than a
            // nested u-each on <option>: that each's clones are appended asynchronously, so
            // the <select>'s own u-prop would apply its value before any <option> existed to
            // select, i.e. every rule's "value" field would appear reset/blank each time any
            // group/rule anywhere on the page was added or removed (u-each rebuilds the whole
            // list on every mutation, hitting this race for every existing rule at once).
            locationValueOptions(rule) {
                const options = this.valueOptions[rule.location] || {};
                return Object.entries(options)
                    .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
                    .join('');
            },

            addField(type) {
                this.fields.push(blankField(type));
            },
            removeField(index) {
                this.fields.splice(index, 1);
            },
            moveField(index, direction) {
                const target = index + direction;
                if (target < 0 || target >= this.fields.length) {
                    return;
                }
                [ this.fields[index], this.fields[target] ] = [ this.fields[target], this.fields[index] ];
            },
            syncName(field) {
                if (!field.name) {
                    field.name = slugifyName(field.label);
                }
            },
            isTextLike(type) {
                return TEXT_LIKE_TYPES.includes(type);
            },
            isChoice(type) {
                return CHOICE_TYPES.includes(type);
            },
        };
    });

    /**
     * Selfie: `u-data="stream"` (one instance per root) wraps `getUserMedia` into a
     * preview -> snapshot -> canvas -> image flow.
     *
     * @since 1.0
     */
    Youla.data('stream', (root) => ({
        error: null,
        canvas: null,
        second: 0,
        photo: '',
        videoRef: { 'u-ref': 'video' },
        imageRef: { 'u-ref': 'image' },
        canvasRef: { 'u-ref': 'canvas' },
        get refs() {
            return {
                video:  root.querySelector('[u-ref="video"]'),
                image:  root.querySelector('[u-ref="image"]'),
                canvas: root.querySelector('[u-ref="canvas"]'),
            };
        },
        check() {
            const { video, image } = this.refs;

            if (!video) {
                console.error('Video for selfie preview is undefined');
                return false;
            }

            if (!image) {
                console.error('Image for output selfie is undefined');
                return false;
            }

            return true;
        },
        getCanvas() {
            return this.refs.canvas || (this.canvas || (this.canvas = document.createElement('canvas')));
        },
        isVisible(element) {
            const styles = window.getComputedStyle(element);
            if (styles) {
                return !(styles.visibility === 'hidden' || styles.display === 'none' || parseFloat(styles.opacity) === 0);
            }
            return false;
        },
        async requestStream(video) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.error = 'unsupported';
                return;
            }

            try {
                video.srcObject = video._x_stream = await navigator.mediaDevices.getUserMedia({video: true});
                this.error = null;
            } catch (error) {
                this.error = error.name === 'NotAllowedError' || error.name === 'SecurityError' ? 'denied' : 'unavailable';
            }
        },
        start() {
            const video = this.refs.video;
            if (video._x_stream || video._x_streamObserver) {
                return;
            }

            if (this.isVisible(video)) {
                this.requestStream(video);
                return;
            }

            video._x_streamObserver = new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting) && this.isVisible(video)) {
                    video._x_streamObserver.disconnect();
                    video._x_streamObserver = null;
                    this.requestStream(video);
                }
            });
            video._x_streamObserver.observe(video);
        },
        snap() {
            if (!this.check()) {
                return null;
            }
            this.start();

            const canvas = this.getCanvas();
            const { video, image } = this.refs;

            let imageStyles = window.getComputedStyle(image),
                targetRatio = parseInt(imageStyles.width, 10) / parseInt(imageStyles.height, 10);

            let videoWidth  = video.videoWidth,
                videoHeight = video.videoHeight,
                videoRatio  = videoWidth / videoHeight;

            let sWidth, sHeight;
            if (videoRatio > targetRatio) {
                sHeight = videoHeight;
                sWidth  = videoHeight * targetRatio;
            } else {
                sWidth  = videoWidth;
                sHeight = videoWidth / targetRatio;
            }

            let sx = (videoWidth - sWidth) / 2,
                sy = (videoHeight - sHeight) / 2;

            canvas.width  = sWidth;
            canvas.height = sHeight;

            let ctx = canvas.getContext('2d');

            // 1:1 pixel copy of the native camera resolution — no resampling, so no quality is lost
            ctx.drawImage(video, sx, sy, sWidth, sHeight, 0, 0, sWidth, sHeight);

            let imageData = canvas.toDataURL('image/png');
            if ( imageData ) {
                image.src = imageData;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            return imageData;
        },
        /**
         * Counts "seconds" down in "second", then puts the snapshot into "photo".
         *
         * @param {number} seconds
         */
        capture(seconds = 3) {
            if (this.second > 0) {
                return;
            }
            this.second = seconds;

            const timer = setInterval(() => {
                if (--this.second > 0) {
                    return;
                }
                clearInterval(timer);

                this.photo = this.snap() || '';
            }, 1000);
        },
        stop() {
            const video = this.refs.video;
            if (video._x_streamObserver) {
                video._x_streamObserver.disconnect();
                video._x_streamObserver = null;
            }
            if (video._x_stream) {
                video._x_stream.getTracks().forEach(track => track.stop());
            }
            video._x_stream = null;
        },
    }));

    /**
     * Search box: `wrapper`/`button`/`input` are ready-made `u-bind` sets. `button`/`input` carry
     * their own `u-ref`, so the wrapper's Ctrl+K shortcut reaches the input via `$refs`.
     *
     * @since 1.0
     */
    Youla.data('search', () => ({
        currentIdx: -1,
        links: [],
        wrapper: {
            '@click.outside'() {
                this.$el.removeAttribute('open');
            },
            '@keydown.escape'() {
                this.$el.removeAttribute('open');
            },
            '@keydown.prevent.window.ctrl.k'() {
                this.$refs.searchButton.click();
            },
        },
        button: {
            'u-ref': 'searchButton',
            '@click'() {
                setTimeout(() => this.$refs.searchInput.focus());
            },
        },
        input: {
            'u-ref': 'searchInput',
            '@keydown.up'() {
                this.currentIdx = this.currentIdx <= 0 ? this.links.length - 1 : this.currentIdx - 1;
                if (!this.links[this.currentIdx]?.url && this.currentIdx === 0) {
                    this.currentIdx = this.links.length - 1;
                }
            },
            '@keydown.down'() {
                this.currentIdx = this.currentIdx >= this.links.length - 1 ? 0 : this.currentIdx + 1;
                if (!this.links[this.currentIdx]?.url) {
                    this.currentIdx++;
                }
            },
            '@keydown.enter'() {
                this.links[this.currentIdx] && (window.location.href = this.links[this.currentIdx].url);
            },
        },
    }));

    /**
     * Slug of an entry, the `slug` form template, configured in `u-data="{ ...slug, base, suffix, from, parent, bases, synced }"`.
     * The server makes it from the source field named by `from` while `synced` and from the typed text,
     * 500ms after the last input. Enter keeps it without submitting, Escape brings back the value it had,
     * an empty one is made from the source again. Values set by the server come with an untrusted
     * `input` event, so they do not stop the following.
     *
     * @since 2027.1
     */
    Youla.data('slug', (root) => ({
        value: root.querySelector?.('.slug-line input')?.value || '',
        base: '',
        suffix: '',
        from: '',
        parent: '',
        bases: {},
        synced: false,
        before: null,
        ajax: null,
        source() {
            return this.from ? root.closest('form')?.elements.namedItem(this.from) : null;
        },
        /**
         * Slug of the text by Sanitizer::slug() on the server, with the transliteration
         * of the Permalinks settings.
         *
         * @param {string} text
         * @returns {Promise<string>}
         */
        generate(text) {
            return String(text).trim() === '' ? Promise.resolve('') : this.ajax.post('slugs/generate', { text }, null, { clearErrors: false });
        },
        follow() {
            const text = this.source()?.value || '';

            return this.generate(text).then((slug) => {
                // typing in the slug while the request went stops the following
                if (this.synced && document.activeElement !== this.$refs.slug) {
                    this.value = slug;
                }
            });
        },
        /**
         * Tell $dirty about a value set here: it comes without an `input` event,
         * so `change` is sent once the field shows it.
         */
        touch() {
            setTimeout(() => this.$refs.slug?.dispatchEvent(new Event('change', { bubbles: true })));
        },
        line: {
            // $ajax reaches only string expressions, not the listener functions
            '@load': 'ajax = $ajax; synced && follow()',
            '@input.document.delay.500ms'(e) {
                if (this.synced && e.target === this.source()) {
                    this.follow();
                }
            },
            // the URL as shown, for the links to the page outside the field, e.g. in the breadcrumbs
            ':data-url'() {
                const url = this.base + this.value + this.suffix;
                root.dispatchEvent(new CustomEvent('slug-url', { detail: url, bubbles: true }));

                return url;
            },
            // the URL of the chosen parent, before saving
            '@change.document'(e) {
                if (this.parent && e.target.name === this.parent && e.target.form === root.closest('form')) {
                    this.base = this.bases[e.target.value] ?? this.base;
                }
            },
        },
        input: {
            'u-ref': 'slug',
            ':value'() {
                return this.value;
            },
            '@focus'() {
                this.before = { value: this.value, synced: this.synced };
            },
            '@input.delay.500ms'(e) {
                const el = e.target;
                if (!e.isTrusted) {
                    this.value = el.value;

                    // a value from the server is the saved one: the form watched by $dirty stays clean
                    this.$dirty.remove(el.form);
                    return;
                }

                // the field is left before the delay ended: blur takes the text
                if (document.activeElement !== el) {
                    return;
                }

                const text  = el.value;
                this.value  = text;
                this.synced = false;
                this.generate(text).then((slug) => {
                    // the text changed while the request went: the next input sends it again
                    if (el.value !== text) {
                        return;
                    }

                    // a separator being typed at the end stays until the field is left
                    const next = slug && document.activeElement === el && /[^\p{L}\p{M}\p{N}_]$/u.test(text) ? `${slug}-` : slug;
                    if (next !== text) {
                        const tail = text.length - el.selectionEnd;
                        el.value   = next;
                        el.setSelectionRange(Math.max(0, next.length - tail), Math.max(0, next.length - tail));
                    }
                    this.value = next;
                });
            },
            '@keydown.enter.prevent'() {
                this.$el.blur();
            },
            '@keydown.escape'() {
                if (this.before) {
                    this.$el.value = this.before.value;
                    this.value     = this.before.value;
                    this.synced    = this.before.synced;
                    this.touch();
                }
                this.$el.blur();
            },
            '@blur'() {
                // the field holds the text typed during the delay of `input`
                const typed = this.$el.value;
                if (typed !== this.value) {
                    this.value  = typed;
                    this.synced = false;
                }
                this.before = null;
                this.generate(typed).then((slug) => {
                    this.synced = this.synced || slug === '';
                    if (slug === '') {
                        return this.follow();
                    }
                    this.value = slug;
                }).then(() => {
                    if (this.value !== typed) {
                        this.touch();
                    }
                });
            },
        },
    }));

    /**
     * Tabs, synced with the page URL: `u-data="tab as tab"` on the wrapper, `u-bind="tab.button('id')"`
     * on each tab button, `u-show="tab.active === 'id'"` or `u-bind="tab.content('id')"` on each panel.
     * The active tab starts from the button the server marks `is-active`: the tab of `?tab=`, else the first.
     *
     * @since 1.0
     */
    Youla.data('tab', () => ({
        active: null,
        button(id) {
            return {
                ':class'() {
                    this.active ??= this.$el.classList.contains('is-active') ? id : null;

                    return { 'is-active': this.active === id };
                },
                '@click'() {
                    this.active = id;

                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', id);
                    window.history.pushState({}, '', url);
                },
            };
        },
        content(id) {
            return {
                'u-show'() {
                    this.active ??= this.$el.hidden ? null : id;

                    return this.active === id;
                },
            };
        },
    }));

    /**
     * Saved state of the forms watched by `$dirty`, by form; null until the first snapshot.
     * Kept here, not in the factory: it re-runs on every expression evaluation page-wide.
     *
     * @type {Map<HTMLFormElement, string|null>}
     */
    const dirtyStates = new Map();

    // Shakes the page instead of following the link, while any watched form is still dirty; a new tab keeps the changes.
    window.addEventListener('click', e => {
        if (document.body.classList.contains('is-unsaved') && e.target.closest('a[href]:not([target="_blank" i])')) {
            e.preventDefault();

            document.body.classList.add('is-shake');
            setTimeout(() => document.body.classList.remove('is-shake'), 500);
        }
    }, true);

    /**
     * `$dirty` — warns about unsaved form changes. Call `$dirty.watch($el)` once per form (e.g.
     * `<form @load="$dirty.watch($el)">`) and `$dirty.remove($el)` after a successful save.
     *
     * @since 1.0
     */
    Youla.variable('dirty', () => {
        const serialize = form => JSON.stringify(Object.fromEntries(new FormData(form).entries()));

        const sync = () => {
            let isDirty = false;
            dirtyStates.forEach((state, form) => {
                if (!form.isConnected) {
                    dirtyStates.delete(form);
                } else if (state !== null && state !== serialize(form)) {
                    isDirty = true;
                }
            });

            document.body.classList.toggle('is-unsaved', isDirty);
        };

        return {
            watch(form) {
                if (!(form instanceof HTMLFormElement) || dirtyStates.has(form)) {
                    return;
                }
                dirtyStates.set(form, null);

                // Deferred so the form's own reactive hydration settles first, or that fill-in would register as a "dirty" change.
                setTimeout(() => {
                    dirtyStates.set(form, serialize(form));

                    form.addEventListener('input', sync);
                    form.addEventListener('change', sync);
                    form.addEventListener('reset', () => setTimeout(() => this.remove(form), 0));
                }, 50);
            },
            /**
             * Take the current values of a watched form as the saved ones.
             *
             * @param {HTMLFormElement} form
             */
            remove(form) {
                if (!dirtyStates.has(form)) {
                    return;
                }

                dirtyStates.set(form, serialize(form));
                sync();
            },
        };
    });

    /**
     * Copies a string to the clipboard, e.g. `@click="$copy('Some text', ['is-copied'])"`.
     *
     * @since 1.0
     */
    Youla.method('copy', (e, el) => (subject, classes) => {
        window.navigator.clipboard.writeText(subject).then(() => {
            const toggled       = classes || ['ph-copy', 'ph-check'];
            const classesToggle = () => toggled.forEach(s => el.classList.toggle(s));

            classesToggle();
            setTimeout(classesToggle, 1000);
        });
    });

    /**
     * Steps the sibling number input up or down, e.g. `@click="$spin(-1)"`.
     * Respects its min, max and step, then fires `input` so listeners see the change.
     *
     * @since 1.0
     */
    Youla.method('spin', (e, el) => (direction) => {
        const input = el.parentElement.querySelector('input[type="number"]');

        if (input.step === 'any') {
            input.value = Number(input.value || 0) + direction;
        } else {
            direction < 0 ? input.stepDown() : input.stepUp();
        }

        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    /**
     * Puts a value into the input of the field the element is in, e.g. `@click="$fill('Y-m-d')"`.
     * Fires `input` and `change`, so unsaved changes and field conditions see it.
     *
     * @since 1.0
     */
    Youla.method('fill', (e, el) => (value) => {
        const input = el.closest('.field')?.querySelector('input, textarea');
        if (!input) {
            return;
        }

        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.focus();
    });

    /**
     * Data sanitizing.
     *
     * @since 1.0
     */
    Youla.method('safe', () => ({
        slug(value) {
            // NFD-decomposes accented characters (e.g. "\u00e9" -> "e" + accent), then drops the accents and anything but letters/numbers/spaces/hyphens.
            return value
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^\p{L}\p{N}\s-]/gu, '')
                .trim()
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .toLowerCase();
        },
    }));

    /**
     * Code syntax highlight
     *
     * @since 1.0
     */
    Youla.directive('highlight', (el, output, { modifiers }) => {
        // Wraps el's children in <code> exactly once — a later call would otherwise nest another wrapper around it each time.
        if (el._x_highlighted) {
            return;
        }
        el._x_highlighted = true;

        const lang    = modifiers[0] || 'html';
        const wrapper = document.createElement('code');

        wrapper.className = `language-${lang}`;
        wrapper.append(...el.childNodes);

        el.classList.add('line-numbers');
        el.setAttribute('data-lang', lang.toUpperCase());
        el.replaceChildren(wrapper);
    });

    /**
     * Disable autofill, reliably — the readonly-until-focus trick stops
     * autofill from prefilling the field before the user interacts with it,
     * even when the browser ignores `autocomplete="off"`.
     *
     * @since 1.0
     */
    Youla.directive('noautofill', (el) => {
        // Attaches focus/blur listeners exactly once — a later call would otherwise stack another pair, each fighting over el.readOnly.
        if (el._x_noautofill) {
            return;
        }
        el._x_noautofill = true;

        const lock = () => el.readOnly = true;

        lock();

        el.addEventListener('focus', () => requestAnimationFrame(() => el.readOnly = false));
        el.addEventListener('blur', lock);
    });

    /**
     * Pins a sidebar within its `position: relative` parent's bounds as it scrolls, instead
     * of sticking to the viewport — a taller-than-viewport sidebar scrolls internally.
     *
     * @since 1.0
     */
    Youla.directive('sticky', el => {
        // Attaches its window listeners exactly once — a later call would otherwise stack another "reposition" closure that runs forever.
        if (el._x_sticky) {
            return;
        }
        el._x_sticky = true;

        const parent = el.parentElement;
        if (getComputedStyle(parent).position !== 'relative') {
            console.warn('Youla.js: "u-sticky" requires its parent to have position: relative.');
            return;
        }

        let maxTop, bottom;

        // Fixed bars are taken from the root's scroll-padding, so their heights stay in CSS.
        const measure = () => {
            const root  = getComputedStyle(document.documentElement);
            const style = getComputedStyle(parent);

            maxTop = (parseFloat(root.scrollPaddingTop) || 0) + parseFloat(style.paddingTop);
            bottom = (parseFloat(root.scrollPaddingBottom) || 0) + parseFloat(style.paddingBottom);
        };

        measure();

        el.style.position = 'sticky';
        el.style.top      = `${maxTop}px`;

        let lastScroll = window.scrollY;
        let lastTop    = el.getBoundingClientRect().top;

        /**
         * Moves the element by the scroll delta from where it was actually drawn, clamped between
         * its top and bottom edges being in view, so it never slides ahead of the page.
         */
        const reposition = () => {
            const delta  = window.scrollY - lastScroll;
            const minTop = Math.min(maxTop, window.innerHeight - el.offsetHeight - bottom);
            lastScroll   = window.scrollY;

            el.style.top = `${Math.min(maxTop, Math.max(minTop, lastTop - delta))}px`;
            lastTop      = el.getBoundingClientRect().top;
        };

        window.addEventListener('scroll', reposition, { passive: true });
        window.addEventListener('load', reposition);
        window.addEventListener('resize', () => {
            measure();
            reposition();
        });
    });

    /**
     * Expands or collapses an element with a smooth slide animation, driven by the
     * directive's truthiness (`u-collapse="open"`) rather than a CSS class.
     *
     * @since 1.0
     */
    Youla.directive('collapse', (el, output) => {
        const isOpen   = !!output;
        const duration = 200;
        const props    = ['height', 'paddingTop', 'paddingBottom', 'marginTop', 'marginBottom'];

        // The first call only sets the state, so a panel rendered open does not grow on page load.
        if (el._x_collapse === undefined) {
            el._x_collapse = isOpen;
            el.hidden = !isOpen;
            return;
        }

        // Every panel bound to the same value is re-evaluated when it changes, only the changed ones animate.
        if (el._x_collapse === isOpen) {
            return;
        }
        el._x_collapse = isOpen;
        el.hidden = false;

        el.style.overflow = 'hidden';
        if (isOpen) {
            el.style.display = 'block';
        }

        const from = Object.fromEntries(props.map(prop => [prop, parseFloat(getComputedStyle(el)[prop])]));

        let start;
        function step(timestamp) {
            start ??= timestamp;

            const elapsed = Math.min(timestamp - start, duration);
            const ratio   = isOpen ? elapsed / duration : 1 - elapsed / duration;

            props.forEach(prop => el.style[prop] = `${from[prop] * ratio}px`);

            if (elapsed < duration) {
                requestAnimationFrame(step);
            } else {
                if (!isOpen) {
                    el.style.display = 'none';
                }
                [...props, 'overflow'].forEach(prop => el.style[prop] = '');
            }
        }
        requestAnimationFrame(step);
    });

    /**
     * Animates `--expansa-progress` into view once the element enters the viewport, from/to
     * modifiers as percentages (`u-progress.20.80.600ms`). `to` can also be a reactive bound
     * value, e.g. `u-progress.0.600ms="percent"`. Skips the transition on reduced motion.
     *
     * @since 1.0
     */
    Youla.directive('progress', (el, output, { modifiers, duration, expression }) => {
        const [rawFrom = 0, rawTo = 100] = modifiers;

        const from = parseInt(rawFrom);

        const bound = expression !== '' && !isNaN(parseFloat(output));
        const to    = bound ? parseFloat(output) : parseInt(rawTo);

        if (isNaN(from) || isNaN(to)) {
            console.warn('Youla.js: "u-progress" requires numeric from/to modifiers as percentages (or a numeric bound value), e.g. u-progress.20.80.600ms.');
            return;
        }

        const start = Math.min(Math.max(from, 0), 100);
        const end   = Math.min(Math.max(to, 0), 100);

        const transitionDuration = duration ? `${duration.value}${duration.unit}` : '0ms';
        const reducedMotion      = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const apply = (percent, animate) => {
            if (animate && !reducedMotion()) {
                el.style.setProperty('--expansa-progress-transition', `width ${transitionDuration}`);
            }
            el.style.setProperty('--expansa-progress', `${percent}%`);
        };

        // Already revealed — this call is a reactive update to the bound value, not the initial mount.
        if (el._x_progress?.revealed) {
            el._x_progress.end = end;
            apply(end, true);
            return;
        }

        if (el._x_progress) {
            el._x_progress.end = end;
            return;
        }

        el._x_progress = { revealed: false, end };

        new IntersectionObserver(([entry], observer) => {
            if (!entry.isIntersecting) {
                return;
            }
            observer.unobserve(el);

            el._x_progress.revealed = true;

            el.style.setProperty('--expansa-progress', `${start}%`);

            if (reducedMotion()) {
                apply(el._x_progress.end, false);
                return;
            }

            setTimeout(() => apply(el._x_progress.end, true), 500);
        }).observe(el);
    });
});
