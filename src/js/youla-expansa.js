document.addEventListener('youla:init', ()=> {
    // settings of youla-ajax.js from DashboardAssets: API address, CSRF names, error markup, texts
    Youla.ajax = { ...window.youla?.ajax, ...Youla.ajax };

    /**
     * Multi-step wizard: `u-step="condition"` marks a panel's completion; use as `u-data="step"`.
     * `u-step.required` also requires every required field's native `checkValidity()`.
     * `u-step:action="expr"` runs once each time that panel becomes the current one.
     *
     * @since 2027.1
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
     * @since 2027.1
     */
    (() => {
        Youla.variable('notice', () => document.querySelector('[u-data="notice"]')?.__x?.data);

        Youla.data('notice', () => ({
            items: [],
            duration: 7000,
            hovering: false,
            // the hovered container holds every countdown
            self: {
                '@mouseenter'() {
                    this.pause();
                },
                '@mouseleave'() {
                    this.resume();
                },
            },
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
            // freezes every item's countdown where it stood
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
            // picks every countdown back up from where pause() froze it
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
     * open() returns a promise of the value the dialog is closed with: `$dialog.close(value)` in the
     * template, undefined for the close button, Escape or a click outside. confirm() and prompt()
     * open the `tmpl-dialog-confirm` and `tmpl-dialog-prompt` templates of welcome.blade.php.
     *
     * The template describes its dialog by attributes, the options of open() and the data win over them:
     * `<template id="tmpl-post-editor" title="Post Editor" size="lg" position="right" guarded>`.
     *
     * @since 2027.1
     */
    (() => {
        const SIZES     = ['xs', 'sm', 'md', 'lg', 'xl'];
        const POSITIONS = ['center', 'left', 'right'];

        /**
         * The size and position of the next dialog as modifiers: `$dialog.lg.right.open(...)`, the last
         * one of a kind wins; `guarded` asks before a dialog with changed fields is dismissed.
         * open() and restore() get them as their options, the rest of `$dialog` is as is.
         */
        const chain = (dialog, options = {}) => new Proxy(dialog, {
            get(target, name) {
                if (SIZES.includes(name)) {
                    return chain(target, { ...options, size: name });
                }
                if (POSITIONS.includes(name)) {
                    return chain(target, { ...options, position: name });
                }
                if (name === 'guarded') {
                    return chain(target, { ...options, guarded: true });
                }
                if (name === 'open' || name === 'restore') {
                    return (first, second) => target[name](first, second, options);
                }
                const value = target[name];
                return typeof value === 'function' ? value.bind(target) : value;
            },
        });

        Youla.variable('dialog', () => {
            const dialog = document.querySelector('[u-data="dialog"]')?.__x?.data;
            return dialog && chain(dialog);
        });

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

        // the promises of open() by the ID of the dialog, outside the reactive stack
        const resolvers = new Map();

        // the dialogs whose fields the user changed, by ID: only the user fires `input` and `change`,
        // a submitted form counts as saved
        const changed = new Set();
        const idOf    = (element) => Number(element.closest('.dialog-wrapper')?.id.replace('dialog-', '')) || 0;

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
            // each dialog of the stack has its own size and position, Esc closes the top one
            self: {
                ':class'() {
                    return this.stack.length ? 'is-active' : '';
                },
                '@keydown.esc.window'() {
                    if (this.stack.length) {
                        this.dismiss(this.stack.at(-1));
                    }
                },
                '@input'(event) {
                    changed.add(idOf(event.target));
                },
                '@change'(event) {
                    changed.add(idOf(event.target));
                },
                '@submit'(event) {
                    changed.delete(idOf(event.target));
                },
            },
            wrapper: {
                'u-each': 'entry in stack',
                ':id': "'dialog-' + entry.id",
                ':class': 'entry.modifiers',
                '@click.outside': 'dismiss(entry, $event)',
            },
            // the buttons of confirm(): one with Cancel, or the choices; the last one is focused for Enter
            confirmButton: {
                'u-each': 'button in entry.buttons',
                ':class': "button.danger ? 'btn--danger' : (entry.cancel ? 'btn--primary' : 'btn--outline')",
                'u-text': 'button.label || texts.confirm',
                '@click': 'close(button.value)',
                '@load': 'setTimeout(() => $el.focus(), 50)',
            },

            /**
             * Open the template as a dialog on top of the others; options: `size` xs|sm|md|lg|xl,
             * `position` center|left|right and `guarded`, usually set by the modifiers of `$dialog`,
             * by default the attributes of the template.
             *
             * @return {Promise<*>} The value of close(), undefined when the dialog is dismissed.
             */
            open(templateID, data = {}, options = {}) {
                const template = document.getElementById(templateID);
                if (!template) {
                    console.warn(`Youla.js: no dialog template '${templateID}'.`);
                    return Promise.resolve(undefined);
                }

                const {
                    size = template.getAttribute('size') ?? 'md',
                    position = template.getAttribute('position') ?? 'center',
                    guarded = template.hasAttribute('guarded'),
                } = options;

                if (!SIZES.includes(size) || !POSITIONS.includes(position)) {
                    console.warn(`Youla.js: unknown dialog size '${size}' or position '${position}'.`);
                }

                const isBase = this.stack.length === 0;
                const id     = ++uid;

                // "id"/"content"/"modifiers"/"opened"/"guarded" go last so they always win: a caller's own data can freely
                // include a same-named field (e.g. opening with a Post - which always has its
                // own "id" and "content" - would otherwise silently clobber this entry's real
                // id and, worse, wipe out the dialog's actual markup with the post's own,
                // usually-empty "content" field).
                // u-each (parts/footer.html) initializes each entry itself, wiring up the template's own "@click".
                this.stack.push({
                    title: template.getAttribute('title') ?? '',
                    ...data,
                    id,
                    content: template.innerHTML,
                    modifiers: `dialog-wrapper--${size} dialog-wrapper--${position}`,
                    opened: performance.now(),
                    guarded,
                });

                // Only the base dialog locks scroll — a nested call would read scrollY as 0 (already frozen).
                if (isBase) {
                    lockScroll();
                    searchParamsHandler('dialog', templateID, false);
                }

                root.dispatchEvent(new Event('open', { bubbles: true }));

                return new Promise(resolve => resolvers.set(id, resolve));
            },
            // Closes the top dialog, its open() resolves with the value.
            close(value) {
                const entry = this.stack.at(-1);
                if (!entry) {
                    return;
                }

                this.stack = this.stack.slice(0, -1);

                resolvers.get(entry.id)?.(value);
                resolvers.delete(entry.id);
                changed.delete(entry.id);

                root.dispatchEvent(new Event('close', { bubbles: true }));

                if (this.stack.length === 0) {
                    unlockScroll();
                    searchParamsHandler('dialog', null, true);
                }
            },
            /**
             * Close the dialog and the ones on top of it without a value: its close button, Escape or a click
             * outside the top one; a `guarded` one with changed fields asks first. The click that opened
             * a dialog reaches the document after it, so the clicks before the dialog opened are skipped.
             */
            async dismiss(entry, event) {
                if (event && (entry.id !== this.stack.at(-1)?.id || event.timeStamp < entry.opened)) {
                    return;
                }

                const index = this.stack.findIndex(dialog => dialog.id === entry.id);
                if (index < 0) {
                    return;
                }

                const unsaved = this.stack.slice(index).some(dialog => dialog.guarded && changed.has(dialog.id));
                if (unsaved && !await this.confirm(this.texts.discardChanges, { action: this.texts.discard, danger: true })) {
                    return;
                }

                while (this.stack.some(dialog => dialog.id === entry.id)) {
                    this.close();
                }
            },
            clear() {
                while (this.stack.length) {
                    this.close();
                }
            },
            /**
             * Ask to confirm an action: `if (await $dialog.confirm(texts.deleteItem, { danger: true })) ...`.
             * Options: `title`, `action` the label of the button, `danger` a red button, `items` a list
             * under the message. With `choices`, the labels by value, it asks to choose one of them instead,
             * without Cancel; `danger` is then the value of the red one.
             *
             * @return {Promise<boolean|string|undefined>} Confirmed or not; with `choices` the chosen value,
             *                                             undefined when the dialog is dismissed.
             */
            confirm(message, { title = '', action = '', danger = false, items = [], choices = null } = {}) {
                const buttons = choices
                    ? Object.entries(choices).map(([value, label]) => ({ value, label, danger: danger === value }))
                    : [ { value: true, label: action, danger: danger === true } ];

                return this.open('tmpl-dialog-confirm', { message, title, items, buttons, cancel: !choices })
                    .then(value => choices ? value : value === true);
            },
            /**
             * Ask for a line of text: `const name = await $dialog.prompt('File name', { value: 'new.txt' })`.
             * Options: `title`, `value` the initial text, `action` the label of the button.
             *
             * @return {Promise<string|undefined>} The trimmed text, undefined when the dialog is dismissed.
             */
            prompt(label, { title = '', value = '', action = '' } = {}) {
                return this.open('tmpl-dialog-prompt', { label, title, value, action });
            },
            /**
             * Reopen the dialog of the template left open in a reloaded or shared `?dialog=` URL:
             * `<template id="..." @load="$dialog.restore($el)">`; the callback gives its data, nothing to skip it.
             *
             * @return {Promise<*>|undefined} The promise of open(), undefined when the URL has another dialog.
             */
            async restore(template, callback = () => ({}), options = {}) {
                if (new URLSearchParams(window.location.search).get('dialog') !== template.id) {
                    return;
                }

                const data = await callback();
                if (data) {
                    return this.open(template.id, data, options);
                }
            },
        }));
    })();

    /**
     * Password policy: checks a string against a fixed policy (minimum count per character
     * class, minimum length) and can generate a password satisfying it.
     *
     * @since 2027.1
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
     * @since 2027.1
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
     * Table: the checkboxes of the rows, the bulk actions, the filter, the parts of the list and the pagination.
     * `u-data="table(options)"`: `fields` of Table::filters(), key => {label, icon, options, param}, `rows`
     * of Table::activeFilters() and `parts`, the query parameters of the parts of the list by key, see Table::headData().
     * The markup of parts/table/table-header binds to it by `u-bind`, the values the handlers need are in `data-*`;
     * every change of a filter, a part or the page reloads the rows and the bar of the header through the API.
     *
     * @since 2027.1
     */
    Youla.data('table', (root) => ({ fields = {}, rows = [], parts = {} } = {}) => {
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
            fields,
            rows,
            parts,
            // the text of the search field, applied with the filters and shown as a chip
            search: new URLSearchParams(window.location.search).get('s') || '',
            // index of the filter whose chip opened the values popover
            editing: null,
            anchor: null,
            bulk: 0,
            // the bulk action chosen in the bar of the header
            action: '',
            trigger: {
                '@change': 'selectAll($el, $root)',
            },
            item: {
                '@click': 'selectItem($el, $root, $event)',
            },
            tools: {
                'u-show'() {
                    return !this.bulk;
                },
            },
            bulkGroup: {
                'u-show'() {
                    return this.bulk > 0;
                },
            },
            // the API route of `action` gets the IDs of the checked rows, the `data-confirm` actions ask first
            bulkForm: {
                async '@submit.prevent'() {
                    // the helpers are read before the dialog: after it, the handler is out of its evaluation
                    const { $el: form, $ajax } = this;
                    if (parse(form.dataset.confirm, []).includes(this.action)
                        && !await this.$dialog.confirm(this.texts.deleteItems, { action: this.texts.deletePermanently, danger: true })) {
                        return;
                    }

                    const ids = this.items(root).filter(input => input.checked).map(input => input.value).join(',');
                    $ajax.post(form.getAttribute('action'), { ids });
                },
            },
            // the `data-password` actions of the form need the current password
            bulkPassword: {
                'u-show'() {
                    return parse(this.$el.closest('form')?.dataset.password, []).includes(this.action);
                },
            },
            bulkApply: {
                ':disabled'() {
                    return !this.action;
                },
            },
            uploader: {
                '@click'() {
                    this.$dialog.open('tmpl-media-uploader');
                },
            },
            emptyTrash: {
                async '@click'() {
                    const { $el, $ajax } = this;
                    const { endpoint, payload } = $el.dataset;
                    if (await this.$dialog.confirm(this.texts.emptyTrash, { action: this.texts.deletePermanently, danger: true })) {
                        $ajax.post(endpoint, parse(payload, {}));
                    }
                },
            },
            // a link of a row, see Table::action(): `data-confirm` is the key of the text asking first,
            // `data-refresh` shows the rows again after the response
            rowAction: {
                async '@click.prevent'() {
                    const { $el, $ajax } = this;
                    const { endpoint, payload, confirm: question, refresh } = $el.dataset;
                    if (question && !await this.$dialog.confirm(this.texts[question] ?? question, { danger: true })) {
                        return;
                    }

                    $ajax.post(endpoint, parse(payload, {})).then(() => refresh && this.applyFilters());
                },
            },
            // the badge "+N more" of a cell shows the links hidden before it
            moreLinks: {
                '@click'() {
                    this.$el.previousElementSibling?.removeAttribute('hidden');
                    this.$el.hidden = true;
                },
            },
            // a field of the list sets the query parameter of its name, an empty value drops it
            queryField: {
                '@change'() {
                    this.setQuery({ [this.$el.name]: this.$el.value });
                },
            },
            sortLink: {
                '@click.prevent'() {
                    const { orderby, order } = this.$el.dataset;
                    this.setQuery({ orderby, order });
                },
            },
            pageLink: {
                '@click.prevent'() {
                    this.goToPage(Number(this.$el.dataset.page));
                },
            },
            pageField: {
                '@change'() {
                    const field = this.$el;
                    if (field.value) {
                        this.goToPage(Math.min(Number(field.max), Math.max(1, Math.trunc(field.value))));
                    }
                },
            },
            searchField: {
                '@input.delay.500ms'() {
                    this.applyFilters();
                },
                '@keydown.enter.prevent'() {
                    this.applyFilters();
                },
            },
            partSelect: {
                '@change'() {
                    this.setPart(this.$el.value);
                },
            },
            filterCount: {
                'u-show'() {
                    return this.activeFilters().length > 0;
                },
                'u-text'() {
                    return this.activeFilters().length;
                },
            },
            filterAdd: {
                '@click'() {
                    this.addFilter(this.$el.dataset.field);
                },
            },
            filterClear: {
                'u-show'() {
                    return this.rows.length > 0;
                },
                '@click'() {
                    this.clearFilters();
                },
            },
            filterEmpty: {
                'u-show'() {
                    return !this.rows.length;
                },
            },
            // in the rows of `u-each` the expressions are strings: only they get `row` and `i`
            filterConjunction: {
                'u-text': 'i ? texts.and : texts.where',
            },
            filterField: {
                ':value': 'row.field',
                '@change': 'setFilterField(i, $el.value)',
                'u-select': 'selectOptions()',
            },
            filterOperator: {
                ':value': 'row.operator',
                '@change': 'setFilterOperator(i, $el.value)',
                'u-select': 'selectOptions()',
            },
            // u-html before :value, as in `builder`: options must exist before the value is selected
            filterValues: {
                'u-html': 'filterOptions(row)',
                ':value': 'row.values',
                '@change': 'setFilterValues(i, $el)',
                'u-select': 'selectOptions()',
            },
            filterRemove: {
                '@click': 'removeFilter(i)',
            },
            chips: {
                'u-show'() {
                    return this.activeFilters().length > 0 || this.search.trim() !== '';
                },
            },
            searchChip: {
                'u-show'() {
                    return this.search.trim() !== '';
                },
            },
            searchChipEdit: {
                '@click'() {
                    root.querySelector('.table-filter [name=s]')?.focus();
                },
            },
            searchChipText: {
                'u-text'() {
                    return this.search.trim();
                },
            },
            searchChipRemove: {
                '@click'() {
                    this.clearSearch();
                },
            },
            filterChip: {
                'u-show': 'row.values.length',
            },
            filterChipAnd: {
                'u-show': 'search.trim() || !isFirstActive(i)',
            },
            filterChipEdit: {
                '@click': 'editing = i',
            },
            filterChipIcon: {
                ':class': 'fields[row.field]?.icon',
            },
            filterChipLabel: {
                'u-text': 'fields[row.field]?.label',
            },
            filterChipOperator: {
                'u-text': "row.operator === 'not' ? texts.isNot : texts.is",
            },
            filterChipValues: {
                'u-text': 'filterLabels(row, texts.selected)',
            },
            filterChipRemove: {
                '@click': 'removeFilter(i)',
            },
            clearAll: {
                '@click'() {
                    this.clearFilters();
                },
            },
            valuesPopover: {
                'u-html'() {
                    return this.filterChoices(this.editing);
                },
                '@change'(e) {
                    this.toggleFilterValue(e.target);
                },
            },
            // the selects of the filter rows open inside the popover, so a click on them does not close it
            selectOptions() {
                return { contentLocation: root.querySelector('.table-filter-content') };
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
                this.action = '';
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
            /**
             * Open a part of the list, see Table::headData(): its query parameters are set, those of the other parts dropped.
             *
             * @param {string} key
             */
            setPart(key) {
                const names = new Set(Object.values(this.parts).flatMap(Object.keys));

                this.setQuery(Object.fromEntries([ ...names ].map(name => [ name, this.parts[key]?.[name] ?? '' ])));
            },
            /**
             * Set query parameters of the page, an empty value drops one, and show the list from the first page.
             *
             * @param {Object<string, string>} values
             */
            setQuery(values) {
                const params = new URLSearchParams(window.location.search);
                Object.entries(values).forEach(([ name, value ]) => value === '' || value == null ? params.delete(name) : params.set(name, value));

                window.history.replaceState(null, '', '?' + this.query(params));
                this.goToPage(1);
            },
            // The rows are fewer or other after a change of the filters, so the list starts from the first page.
            applyFilters() {
                this.goToPage(1);
            },
            // URLSearchParams escapes the separators of the filters: `,` of the values and `!` of "is not".
            query(params) {
                return params.toString().replace(/%2C/gi, ',').replace(/%21/g, '!');
            },
            param(field) {
                return this.fields[field]?.param || field;
            },
            // A filter is the parameter of its field, `author=1,3`, or with `!` for "is not", `role!=editor`.
            goToPage(page) {
                const params = new URLSearchParams(window.location.search);
                Object.keys(this.fields).forEach(field => {
                    params.delete(this.param(field));
                    params.delete(`${this.param(field)}!`);
                });
                page > 1 ? params.set('page', page) : params.delete('page');

                const search = this.search.trim();
                search ? params.set('s', search) : params.delete('s');

                // rows of the same field and operator are one parameter, their values are joined
                this.activeFilters().forEach(row => {
                    const name   = this.param(row.field) + (row.operator === 'not' ? '!' : '');
                    const values = new Set([ ...(params.get(name)?.split(',') || []), ...row.values ]);
                    params.set(name, [ ...values ].join(','));
                });

                window.history.replaceState(null, '', '?' + this.query(params));

                // the API builds the table from the same query as the page; the rows and the bar of the header are replaced,
                // their bindings to this component are initialized again; from the root a new request aborts the previous one
                this.$ajax.post('tables/filter', Object.fromEntries(params), null, { el: root }).then(() => {
                    this.resetAll(root);
                    [ '.table-body', '.table-header', '.table-head-data' ]
                        .map(selector => root.querySelector(selector))
                        .forEach(el => el && root.__x.initialize(el));
                });
            },
        };
    });

    /**
     * Custom fields builder - the whole Custom Fields group editor page
     * (dashboard/views/field-groups.blade.php): title/status, its location rule groups
     * (ORed together, rules within a group ANDed - see `form/builder.blade.php`, embedded
     * as a plain markup partial rather than its own component) and its field rows.
     * The group and the values of the location rules come as the arguments:
     * `u-data="builder(<?php echo escape(Json::encode($state)); ?>, <?php echo escape(Json::encode($valueOptions)); ?>)"`.
     *
     * @since 2027.1
     */
    Youla.data('builder', () => (state = {}, valueOptions = {}) => {

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
     * @since 2027.1
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
     * Search box, a popover: the browser opens it by the button, closes it on Esc and a click outside
     * and focuses the `autofocus` input; `input` is the `u-bind` set of the input with Ctrl+K and the keys
     * of the results. The typed text goes to the `search/find` endpoint, which answers with
     * `{links: [{url, text}]}`; a link without a URL is a section header. A plain array would be taken
     * by $ajax for fragments.
     *
     * @since 2027.1
     */
    Youla.data('search', () => ({
        currentIdx: -1,
        links: [],
        query: '',
        // the text of the last finished search: the states of the box change only when the answer comes
        searched: '',
        find() {
            // the server searches from 2 characters, see App\Api\Search\SearchService
            const query = this.query.trim();
            if (query.length < 2) {
                this.links    = [];
                this.searched = '';
                return;
            }

            // an answer to an older text that came late is dropped
            const show = (links) => {
                if (query === this.query.trim()) {
                    this.links      = links;
                    this.searched   = query;
                    this.currentIdx = -1;
                }
            };

            this.$ajax.post('search/find', { query })
                .then((result) => show(Array.isArray(result?.links) ? result.links : []))
                .catch(() => show([]));
        },
        input: {
            '@keydown.prevent.window.ctrl.k'() {
                // showPopover() throws on an open popover, then only the focus is back
                const box = this.$el.closest('[popover]');
                box.matches(':popover-open') ? this.$el.focus() : box.showPopover();
            },
            '@input.delay.250ms'() {
                this.query = this.$el.value;
                this.find();
            },
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
        results: {
            'u-show': 'links.length > 0',
        },
        row: {
            'u-each': '(link, i) in links',
            ':class': "link.url && i === currentIdx && 'is-active'",
        },
        anchor: {
            ':href': 'link.url',
            'u-show': 'link.url',
            'u-text': 'link.text',
        },
        heading: {
            'u-show': '!link.url',
            'u-text': 'link.text',
        },
        idle: {
            'u-show': '!links.length && !searched.trim()',
        },
        empty: {
            'u-show': "!links.length && searched.trim() !== ''",
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
            return String(text).trim() === '' ? Promise.resolve('') : this.$ajax.post('slugs/generate', { text }, null, { clearErrors: false });
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
            '@load': 'synced && follow()',
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
            // the URL of the chosen parent, before saving; a parent found by the search brings it in `data-base`
            '@change.document'(e) {
                if (this.parent && e.target.name === this.parent && e.target.form === root.closest('form')) {
                    this.base = this.bases[e.target.value] ?? e.target.selectedOptions?.[0]?.dataset.base ?? this.base;
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
     * Details aside the post editor: the status follows the date as Status::byDate() on the server does,
     * a published post dated ahead is scheduled, a scheduled one dated now or before is published.
     * The saved status comes as the argument: `u-data="postDetails(<?php echo htmlspecialchars(json_encode($post->status)); ?>)"`.
     *
     * @since 2027.1
     */
    Youla.data('postDetails', (root) => (saved = '') => ({
        status: saved,
        // a field changed or reset by the form goes through the status rule
        self: {
            '@change'() {
                this.schedule(this.$event.target);
            },
            '@reset.document'() {
                this.restore(this.$event.target);
            },
        },
        // the hidden field of the datepicker sends `change` with the ISO value
        schedule(input) {
            if (input.name !== 'created_at' || !input.value) {
                return;
            }

            const ahead = new Date(input.value) > new Date();
            if (this.status === 'publish' && ahead) {
                this.status = 'future';
            } else if (this.status === 'future' && !ahead) {
                this.status = 'publish';
            }
        },
        // a reset of the form brings the select back, the data of the status follows it
        restore(form) {
            if (form === root.closest('form')) {
                this.status = saved;
            }
        },
    }));

    /**
     * Tabs, synced with the page URL: `u-data="tab as tab"` on the wrapper, `u-bind="tab.button('id')"`
     * on each tab button, `u-show="tab.active === 'id'"` or `u-bind="tab.content('id')"` on each panel.
     * The active tab starts from the button the server marks `is-active`: the tab of `?tab=`, else the first.
     *
     * @since 2027.1
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
     * @since 2027.1
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
     * @since 2027.1
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
     * @since 2027.1
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
     * @since 2027.1
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
     * Code syntax highlight
     *
     * @since 2027.1
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
     * Length counter of a text with `maxlength` or `minlength`: `u-counter` on the input or textarea,
     * the `.field-counter` of its field shows `12 / 160` (or `12` without the maximum) as the text is typed.
     * The counter gets `is-short` while a typed text is shorter than the minimum and `is-full` at the maximum.
     * The length is counted as the browser counts it for the limits, in UTF-16 units.
     *
     * @since 2027.1
     */
    Youla.directive('counter', (el) => {
        const counter = el.closest('.field')?.querySelector('.field-counter');
        if (!counter) {
            return;
        }

        const update = () => {
            const length = el.value.length;
            const min    = +el.getAttribute('minlength') || 0;
            const max    = +el.getAttribute('maxlength') || 0;

            counter.textContent = max ? `${length} / ${max}` : `${length}`;
            counter.classList.toggle('is-short', length > 0 && length < min);
            counter.classList.toggle('is-full', max > 0 && length >= max);
        };

        update();

        // listens once: a later call only brings the text up to date, e.g. after u-prop wrote the value
        if (!el._x_counter) {
            el._x_counter = true;
            el.addEventListener('input', update);
            el.addEventListener('change', update);
        }
    });

    /**
     * Disable autofill, reliably — the readonly-until-focus trick stops
     * autofill from prefilling the field before the user interacts with it,
     * even when the browser ignores `autocomplete="off"`.
     *
     * @since 2027.1
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
     * @since 2027.1
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
     * Sorting by dragging: the direct children of the list are its items, the dragged one follows the
     * pointer, the others make room for it. On drop the items are moved in the page and the list gets
     * a `sort` event: `{ from, count, to, depth }`, the first moved item, the number of moved items,
     * their place in the list without them and the depth of the first one. Mouse, pen and touch.
     *
     * `u-sortable="{ handle: '.sortable-handle', nested: true, indent: 24, maxDepth: 5 }"`, all optional:
     * the handle starts dragging (the whole item without it, then touch can't scroll the list); a nested
     * list reads the depths of the items from `--depth` of their style, an item moves with the deeper items after it
     * and the horizontal offset changes the depth by `indent` pixels per level.
     *
     * @since 2027.1
     */
    (() => {
        // pointer offset that tells dragging from a click
        const THRESHOLD = 4;
        // edge of the scroller where it scrolls by itself, and the fastest step
        const EDGE  = 48;
        const SPEED = 16;
        const LANDING = 150;

        const itemsOf = list => [ ...list.children ].filter(el => !el.hasAttribute('u-each') && !el.hidden && el.tagName !== 'TEMPLATE');
        const depthOf = el => +(el.style.getPropertyValue('--depth') || 0);

        function scrollerOf(el) {
            for (let node = el.parentElement; node && node !== document.body; node = node.parentElement) {
                const { overflowY } = getComputedStyle(node);
                if ((overflowY === 'auto' || overflowY === 'scroll') && node.scrollHeight > node.clientHeight) {
                    return node;
                }
            }
            return document.scrollingElement;
        }

        function start(list, event) {
            const options = list._x_sortable;
            const row     = itemsOf(list).find(item => item.contains(event.target));
            if (!row || event.button !== 0 || (options.handle ? !event.target.closest(options.handle) : event.target.closest('input, textarea, select, [contenteditable]'))) {
                return;
            }

            const startX = event.clientX;
            const startY = event.clientY;
            let drag = null;

            const move = event => {
                if (!drag) {
                    if (Math.hypot(event.clientX - startX, event.clientY - startY) < THRESHOLD) {
                        return;
                    }
                    drag = begin(list, row, startX, startY);
                }
                event.preventDefault();
                drag.pointer = event;
                update(drag);
            };
            const stop = () => {
                document.removeEventListener('pointermove', move);
                document.removeEventListener('pointerup', stop);
                document.removeEventListener('pointercancel', stop);
                if (drag) {
                    // the click after dragging would select or open the item
                    const swallow = event => event.stopPropagation();
                    window.addEventListener('click', swallow, { capture: true, once: true });
                    setTimeout(() => window.removeEventListener('click', swallow, { capture: true }), 0);
                    finish(drag);
                }
            };

            document.addEventListener('pointermove', move, { passive: false });
            document.addEventListener('pointerup', stop);
            document.addEventListener('pointercancel', stop);
        }

        function begin(list, row, startX, startY) {
            const options  = list._x_sortable;
            const rows     = itemsOf(list);
            const from     = rows.indexOf(row);
            let count      = 1;
            while (options.nested && from + count < rows.length && depthOf(rows[from + count]) > depthOf(row)) {
                count++;
            }

            const scroller = scrollerOf(list);
            const offset   = scroller.scrollTop;
            // tops and bottoms in the coordinates of the scrolled content
            const rects    = rows.map(el => {
                const rect = el.getBoundingClientRect();
                return { top: rect.top + offset, bottom: rect.bottom + offset };
            });
            const block    = rows.slice(from, from + count);
            const others   = rows.filter(el => !block.includes(el)).map(el => ({ el, rect: rects[rows.indexOf(el)] }));
            const gap      = rows.length > 1 ? Math.max(0, rects[1].top - rects[0].bottom) : 0;
            const top      = rects[from].top;
            const bottom   = rects[from + count - 1].bottom;
            const deepest  = Math.max(...block.map(depthOf)) - depthOf(row);

            list.classList.add('is-sorting');
            block.forEach(el => el.classList.add('is-dragging'));

            const drag = {
                list, rows, block, others, from, count, top, bottom, scroller, startX, startY, offset,
                shift: bottom - top + gap,
                depth: depthOf(row),
                maxDepth: Math.max(depthOf(row), (options.maxDepth ?? Infinity) - deepest),
                to: from,
                pointer: null,
            };
            autoscroll(drag);

            return drag;
        }

        function update(drag) {
            const { list, block, others, top, bottom, scroller, pointer } = drag;
            const options = list._x_sortable;
            const dx      = pointer.clientX - drag.startX;
            const dy      = pointer.clientY - drag.startY + scroller.scrollTop - drag.offset;
            const center  = (top + bottom) / 2 + dy;

            // the block goes before the first item whose middle is below its middle
            const to = others.filter(({ rect }) => (rect.top + rect.bottom) / 2 < center).length;
            others.forEach(({ el }, index) => {
                const before = drag.rows.indexOf(el) < drag.from;
                const y      = before && index >= to ? drag.shift : !before && index < to ? -drag.shift : 0;
                el.style.transform = y ? `translateY(${y}px)` : '';
            });

            let depth = depthOf(drag.rows[drag.from]);
            if (options.nested) {
                // at most one level deeper than the item above, at least as deep as the item below:
                // between a parent and its subitem the block joins the subitems instead of taking them
                const above = others[to - 1]?.el;
                const below = others[to]?.el;
                const limit = Math.min(above ? depthOf(above) + 1 : 0, drag.maxDepth);
                const lower = below ? Math.min(depthOf(below), limit) : 0;
                depth = Math.max(lower, Math.min(depth + Math.round(dx / (options.indent || 24)), limit));
            }

            drag.to    = to;
            drag.depth = depth;
            drag.x     = (depth - depthOf(drag.rows[drag.from])) * (options.indent || 24);
            block.forEach(el => el.style.transform = `translate(${drag.x}px, ${dy}px)`);
        }

        // scrolls the scroller while the pointer is near its edge
        function autoscroll(drag) {
            const step = () => {
                if (drag.done) {
                    return;
                }
                if (drag.pointer) {
                    const area  = drag.scroller === document.scrollingElement
                        ? { top: 0, bottom: window.innerHeight }
                        : drag.scroller.getBoundingClientRect();
                    const { clientY } = drag.pointer;
                    const speed = clientY < area.top + EDGE ? -SPEED * (1 - (clientY - area.top) / EDGE)
                        : clientY > area.bottom - EDGE ? SPEED * (1 - (area.bottom - clientY) / EDGE) : 0;
                    if (speed) {
                        drag.scroller.scrollTop += Math.round(speed);
                        update(drag);
                    }
                }
                requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        }

        function finish(drag) {
            const { list, rows, block, others, from, count, to, top, bottom } = drag;
            drag.done = true;

            // the slot of the block: where the item it takes the place of was
            const landing = to < from ? others[to].rect.top - top
                : to > from ? others[to - 1].rect.bottom - bottom : 0;
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            block.forEach(el => {
                el.style.transition = reduced ? '' : `transform ${LANDING}ms ease`;
                el.style.transform  = `translate(${drag.x || 0}px, ${landing}px)`;
            });

            setTimeout(() => {
                const depth   = drag.depth;
                const shift   = depth - depthOf(rows[from]);
                const changed = to !== from || shift !== 0;

                // the items move in the page too: a list without u-each stays sorted
                if (to !== from) {
                    const anchor = to < others.length ? others[to].el : others[others.length - 1].el.nextSibling;
                    block.forEach(el => list.insertBefore(el, anchor));
                }
                if (list._x_sortable.nested && shift !== 0) {
                    block.forEach(el => el.style.setProperty('--depth', depthOf(el) + shift));
                }

                list.classList.remove('is-sorting');
                rows.forEach(el => {
                    el.classList.remove('is-dragging');
                    el.style.transform  = '';
                    el.style.transition = '';
                });

                if (changed) {
                    list.dispatchEvent(new CustomEvent('sort', { detail: { from, count, to, depth }, bubbles: true }));
                }
            }, reduced ? 0 : LANDING);
        }

        Youla.directive('sortable', (el, output) => {
            el._x_sortable = output && typeof output === 'object' ? output : {};
            if (!el._x_sortableBound) {
                el._x_sortableBound = true;
                el.addEventListener('pointerdown', event => start(el, event));
            }
        });
    })();

    /**
     * Expands or collapses an element with a smooth slide animation, driven by the
     * directive's truthiness (`u-collapse="open"`) rather than a CSS class.
     *
     * @since 2027.1
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
                // an open panel gets its display from CSS again, e.g. grid instead of block
                el.style.display = isOpen ? '' : 'none';
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
     * @since 2027.1
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
