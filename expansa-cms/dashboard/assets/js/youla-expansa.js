document.addEventListener('youla:init', () => {
    Youla.ajax = {
        ...window.youla?.ajax,
        ...Youla.ajax
    };
    (() => {
        function hasUData(el) {
            return [ ...el.attributes ].some(({name}) => name === 'u-data' || name.startsWith('u-data.'));
        }
        function closestComponent(el) {
            while (el && !hasUData(el)) {
                el = el.parentElement;
            }
            return el ? el.__x : null;
        }
        const REQUIRED_FIELDS_SELECTOR = 'input[required], select[required], textarea[required]';
        Youla.directive('step', (el, output, attribute, component) => {
            const wizard = component.data;
            const step = wizard.getStep(el);
            const required = attribute.modifiers.includes('required');
            if (required && !el._x_stepRequiredBound) {
                el._x_stepRequiredBound = true;
                el.querySelectorAll(REQUIRED_FIELDS_SELECTOR).forEach(field => {
                    [ 'input', 'change' ].forEach(event => field.addEventListener(event, () => component.refresh(el)));
                });
            }
            let isComplete = required && attribute.expression.trim() === '' ? true : !!output;
            if (required) {
                isComplete = isComplete && [ ...el.querySelectorAll(REQUIRED_FIELDS_SELECTOR) ].every(field => field.checkValidity());
            }
            if (step.isComplete !== isComplete) {
                step.isComplete = isComplete;
            }
        });
        Youla.data('step', () => ({
            steps: [],
            currentIndex: 1,
            progress() {
                const total = this.steps.length;
                const current = Math.min(this.currentIndex, total);
                let complete = 0;
                for (let index = 0; index < current; index++) {
                    if (this.steps[index].isComplete) {
                        complete++;
                    }
                }
                return {
                    total,
                    complete,
                    current,
                    incomplete: total - complete,
                    progress: `${Math.floor(current / total * 100)}%`,
                    completion: `${Math.floor(complete / total * 100)}%`,
                    percentage: Math.floor(complete / total * 100)
                };
            },
            stepAt(index) {
                return this.steps[index - 1] || {
                    el: null
                };
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
            isSteps(...values) {
                return values.some(value => Array.isArray(value) ? this.currentIndex >= value[0] && this.currentIndex <= value[1] : value === this.currentIndex);
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
                    progress: this.progress()
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
                if (index !== null && this.steps[index - 1] !== void 0) {
                    this.currentIndex = index;
                }
                this.render();
                if (this.currentIndex !== previousIndex) {
                    this.runAction(this.steps[this.currentIndex - 1]);
                }
                return this.current();
            },
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
                    const isHidden = index + 1 !== this.currentIndex;
                    if (step.el.hidden !== isHidden) {
                        step.el.hidden = isHidden;
                    }
                });
            },
            getStep(el) {
                let index = el._x_stepIndex;
                if (index === undefined) {
                    index = el._x_stepIndex = this.steps.push({
                        el,
                        isComplete: true
                    }) - 1;
                    this.render();
                }
                return this.steps[index];
            }
        }));
    })();
    (() => {
        Youla.variable('notice', () => document.querySelector('[u-data="notice"]')?.__x?.data);
        Youla.data('notice', () => ({
            items: [],
            duration: 7e3,
            hovering: false,
            self: {
                '@mouseenter'() {
                    this.pause();
                },
                '@mouseleave'() {
                    this.resume();
                }
            },
            info(message, duration) {
                this.add(message, 'info', duration);
            },
            success(message, duration) {
                this.add(message, 'success', duration);
            },
            warning(message, duration) {
                this.add(message, 'warning', duration);
            },
            error(message, duration) {
                this.add(message, 'error', duration);
            },
            loading(message, duration) {
                this.add(message, 'loading', duration);
            },
            pause() {
                this.hovering = true;
                this.items.forEach(item => {
                    if (item.timer) {
                        clearTimeout(item.timer);
                        item.timer = null;
                        item.remaining = Math.max(0, item.remaining - (Date.now() - item.startedAt));
                    }
                });
            },
            resume() {
                this.hovering = false;
                this.items.forEach(item => this.schedule(item.id));
            },
            schedule(id) {
                let item = this.items.find(item => item.id === id);
                if (item && !item.timer && item.duration) {
                    item.startedAt = Date.now();
                    item.timer = setTimeout(() => this.close(id), item.remaining);
                }
            },
            elapsed(item) {
                return item.duration - item.remaining + (item.timer ? Date.now() - item.startedAt : 0);
            },
            close(id) {
                let item = this.items.find(item => item.id === id);
                if (typeof item !== 'undefined') {
                    clearTimeout(item.timer);
                    this.items = this.items.map(item => item.id === id ? {
                        ...item,
                        selectors: [ ...item.selectors, 'hiding' ]
                    } : item);
                    setTimeout(() => {
                        this.items = this.items.filter(item => item.id !== id);
                    }, 1e3);
                }
            },
            add(message, type, duration) {
                if (message) {
                    let id = Date.now();
                    if (duration === 'auto') {
                        duration = Math.max(message.length * 70, 1500);
                    } else if (duration === void 0) {
                        duration = this.duration;
                    }
                    this.items = [ ...this.items, {
                        id,
                        message,
                        closable: true,
                        selectors: [ type || 'info' ],
                        duration,
                        remaining: duration,
                        startedAt: Date.now(),
                        timer: null,
                        classes() {
                            return this.selectors.map(x => 'is-' + x).join(' ');
                        }
                    } ];
                    if (!this.hovering) {
                        this.schedule(id);
                    }
                }
            }
        }));
    })();
    (() => {
        Youla.variable('dialog', () => document.querySelector('[u-data="dialog"]')?.__x?.data);
        const searchParamsHandler = (param, value, isRemove) => {
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);
            if (isRemove) {
                params.delete(param);
            } else {
                params.set(param, value);
            }
            url.search = params.toString();
            window.history.replaceState({}, '', url.toString());
        };
        let uid = 0;
        let scrollY = 0;
        function lockScroll() {
            scrollY = window.scrollY;
            Object.assign(document.body.style, {
                position: 'fixed',
                top: `-${scrollY}px`,
                width: '100%',
                overflow: 'hidden'
            });
        }
        function unlockScroll() {
            Object.assign(document.body.style, {
                position: '',
                top: '',
                width: '',
                overflow: ''
            });
            window.scrollTo({
                top: scrollY,
                left: 0,
                behavior: 'instant'
            });
        }
        Youla.data('dialog', root => ({
            stack: [],
            self: {
                ':class'() {
                    return this.stack.length ? [ 'is-active', this.stack.at(-1)?.class ].filter(Boolean).join(' ') : '';
                },
                '@keydown.esc.window'() {
                    this.close();
                }
            },
            open(templateID, data = {}) {
                setTimeout(() => {
                    let template = document.getElementById(templateID);
                    if (!template) {
                        return;
                    }
                    const isBase = this.stack.length === 0;
                    this.stack.push({
                        ...data,
                        id: ++uid,
                        content: template.innerHTML
                    });
                    if (isBase) {
                        lockScroll();
                    }
                    root.dispatchEvent(new Event('open', {
                        bubbles: true
                    }));
                    if (isBase) {
                        searchParamsHandler('dialog', templateID, false);
                    }
                }, 25);
            },
            close(id) {
                const target = id ?? this.stack.at(-1)?.id;
                this.stack = this.stack.filter(dialog => dialog.id !== target);
                root.dispatchEvent(new Event('close', {
                    bubbles: true
                }));
                if (this.stack.length === 0) {
                    unlockScroll();
                    searchParamsHandler('dialog', null, true);
                }
            },
            clear() {
                [ ...this.stack ].forEach(entry => this.close(entry.id));
            },
            async init(templateID, callback) {
                const params = new URLSearchParams(window.location.search);
                if (templateID && params.get('dialog') === templateID && callback) {
                    const data = await callback();
                    if (data) {
                        this.open(templateID, data);
                    }
                }
            }
        }));
    })();
    Youla.data('password', () => ({
        value: '',
        visible: false,
        progress: 0,
        labels: [ 'Слишком слабый', 'Слабый', 'Средний', 'Хороший', 'Отличный' ],
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
            if (/\s/.test(value)) {
                value = this.value = value.replace(/\s/g, '');
            }
            let matchCount = 0;
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
            this.progress = matchCount / totalWeight * 100;
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
                [array[i], array[j]] = [ array[j], array[i] ];
            }
            return array.join('');
        }
    }));
    Youla.data('avatar', () => ({
        name: '',
        image: '',
        field: {
            'u-prop': 'name'
        },
        picture: {
            ':title': 'name',
            ':style': 'image && `background-image:url(${image})`'
        },
        initials: {
            'u-show': '!image',
            'u-text'() {
                return this.name.trim().split(/\s+/).map(word => word[0]).slice(0, 2).join('').toUpperCase();
            }
        },
        uploader: {
            '@change'() {
                let file = this.$event.target.files[0];
                if (file) {
                    let reader = new FileReader;
                    reader.onload = e => this.image = e.target.result;
                    reader.readAsDataURL(file);
                }
            }
        },
        remover: {
            'u-show': 'image',
            '@click'() {
                let input = this.$root.querySelector('input[type="file"]');
                if (input) {
                    input.value = '';
                }
                this.image = '';
            }
        }
    }));
    Youla.data('table', root => ({fields = {}, rows = [], parts = {}} = {}) => {
        const parse = (json, fallback) => {
            try {
                return JSON.parse(json || '') ?? fallback;
            } catch {
                return fallback;
            }
        };
        const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            '\'': '&#39;'
        }[char]));
        return {
            fields,
            rows,
            parts,
            search: new URLSearchParams(window.location.search).get('s') || '',
            editing: null,
            anchor: null,
            bulk: 0,
            action: '',
            trigger: {
                '@change': 'selectAll($el, $root)'
            },
            item: {
                '@click': 'selectItem($el, $root, $event)'
            },
            tools: {
                'u-show'() {
                    return !this.bulk;
                }
            },
            bulkGroup: {
                'u-show'() {
                    return this.bulk > 0;
                }
            },
            bulkForm: {
                '@submit.prevent'() {
                    const form = this.$el;
                    if (parse(form.dataset.confirm, []).includes(this.action) && !confirm(this.texts.deleteItems)) {
                        return;
                    }
                    const ids = this.items(root).filter(input => input.checked).map(input => input.value).join(',');
                    this.$ajax.post(form.getAttribute('action'), {
                        ids
                    });
                }
            },
            bulkPassword: {
                'u-show'() {
                    return parse(this.$el.closest('form')?.dataset.password, []).includes(this.action);
                }
            },
            bulkApply: {
                ':disabled'() {
                    return !this.action;
                }
            },
            uploader: {
                '@click'() {
                    this.$dialog.open('tmpl-media-uploader', this.uploaderDialog);
                }
            },
            emptyTrash: {
                '@click'() {
                    const {endpoint, payload} = this.$el.dataset;
                    if (confirm(this.texts.emptyTrash)) {
                        this.$ajax.post(endpoint, parse(payload, {}));
                    }
                }
            },
            rowAction: {
                '@click.prevent'() {
                    const {endpoint, payload, confirm: question, refresh} = this.$el.dataset;
                    if (question && !confirm(this.texts[question] ?? question)) {
                        return;
                    }
                    this.$ajax.post(endpoint, parse(payload, {})).then(() => refresh && this.applyFilters());
                }
            },
            moreLinks: {
                '@click'() {
                    this.$el.previousElementSibling?.removeAttribute('hidden');
                    this.$el.hidden = true;
                }
            },
            queryField: {
                '@change'() {
                    this.setQuery({
                        [this.$el.name]: this.$el.value
                    });
                }
            },
            sortLink: {
                '@click.prevent'() {
                    const {orderby, order} = this.$el.dataset;
                    this.setQuery({
                        orderby,
                        order
                    });
                }
            },
            pageLink: {
                '@click.prevent'() {
                    this.goToPage(Number(this.$el.dataset.page));
                }
            },
            pageField: {
                '@change'() {
                    const field = this.$el;
                    if (field.value) {
                        this.goToPage(Math.min(Number(field.max), Math.max(1, Math.trunc(field.value))));
                    }
                }
            },
            searchField: {
                '@input.delay.500ms'() {
                    this.applyFilters();
                },
                '@keydown.enter.prevent'() {
                    this.applyFilters();
                }
            },
            partSelect: {
                '@change'() {
                    this.setPart(this.$el.value);
                }
            },
            filterCount: {
                'u-show'() {
                    return this.activeFilters().length > 0;
                },
                'u-text'() {
                    return this.activeFilters().length;
                }
            },
            filterAdd: {
                '@click'() {
                    this.addFilter(this.$el.dataset.field);
                }
            },
            filterClear: {
                'u-show'() {
                    return this.rows.length > 0;
                },
                '@click'() {
                    this.clearFilters();
                }
            },
            filterEmpty: {
                'u-show'() {
                    return !this.rows.length;
                }
            },
            filterConjunction: {
                'u-text': 'i ? texts.and : texts.where'
            },
            filterField: {
                ':value': 'row.field',
                '@change': 'setFilterField(i, $el.value)',
                'u-select': 'selectOptions()'
            },
            filterOperator: {
                ':value': 'row.operator',
                '@change': 'setFilterOperator(i, $el.value)',
                'u-select': 'selectOptions()'
            },
            filterValues: {
                'u-html': 'filterOptions(row)',
                ':value': 'row.values',
                '@change': 'setFilterValues(i, $el)',
                'u-select': 'selectOptions()'
            },
            filterRemove: {
                '@click': 'removeFilter(i)'
            },
            chips: {
                'u-show'() {
                    return this.activeFilters().length > 0 || this.search.trim() !== '';
                }
            },
            searchChip: {
                'u-show'() {
                    return this.search.trim() !== '';
                }
            },
            searchChipEdit: {
                '@click'() {
                    root.querySelector('.table-filter [name=s]')?.focus();
                }
            },
            searchChipText: {
                'u-text'() {
                    return this.search.trim();
                }
            },
            searchChipRemove: {
                '@click'() {
                    this.clearSearch();
                }
            },
            filterChip: {
                'u-show': 'row.values.length'
            },
            filterChipAnd: {
                'u-show': 'search.trim() || !isFirstActive(i)'
            },
            filterChipEdit: {
                '@click': 'editing = i'
            },
            filterChipIcon: {
                ':class': 'fields[row.field]?.icon'
            },
            filterChipLabel: {
                'u-text': 'fields[row.field]?.label'
            },
            filterChipOperator: {
                'u-text': 'row.operator === \'not\' ? texts.isNot : texts.is'
            },
            filterChipValues: {
                'u-text': 'filterLabels(row, texts.selected)'
            },
            filterChipRemove: {
                '@click': 'removeFilter(i)'
            },
            clearAll: {
                '@click'() {
                    this.clearFilters();
                }
            },
            valuesPopover: {
                'u-html'() {
                    return this.filterChoices(this.editing);
                },
                '@change'(e) {
                    this.toggleFilterValue(e.target);
                }
            },
            selectOptions() {
                return {
                    contentLocation: root.querySelector('.table-filter-content')
                };
            },
            items(root) {
                return [ ...root.querySelectorAll('[u-bind~="item"]') ];
            },
            count(root) {
                this.bulk = this.items(root).filter(input => input.checked).length;
            },
            selectAll(el, root) {
                this.items(root).forEach(input => input.checked = el.checked);
                this.count(root);
            },
            selectItem(el, root, event) {
                let items = this.items(root);
                let index = items.indexOf(el);
                let checked = el.checked;
                let start = event.shiftKey && this.anchor !== null ? this.anchor : index;
                for (let i = Math.min(start, index); i <= Math.max(start, index); i++) {
                    items[i].checked = checked;
                }
                this.anchor = index;
                this.count(root);
            },
            resetAll(root) {
                root.querySelectorAll('[u-bind~="trigger"], [u-bind~="item"]').forEach(input => input.checked = false);
                this.anchor = null;
                this.bulk = 0;
                this.action = '';
            },
            activeFilters() {
                return this.rows.filter(row => row.values.length);
            },
            isFirstActive(index) {
                return this.rows.findIndex(row => row.values.length) === index;
            },
            addFilter(field) {
                this.rows.push({
                    field,
                    operator: 'is',
                    values: []
                });
            },
            removeFilter(index) {
                const [row] = this.rows.splice(index, 1);
                if (row.values.length) {
                    this.applyFilters();
                }
            },
            clearFilters() {
                const applied = this.activeFilters().length || this.search;
                this.rows = [];
                this.search = '';
                if (applied) {
                    this.applyFilters();
                }
            },
            clearSearch() {
                this.search = '';
                this.applyFilters();
            },
            setFilterField(index, field) {
                const row = this.rows[index];
                if (row.field === field) {
                    return;
                }
                const applied = row.values.length;
                row.field = field;
                row.values = [];
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
            filterOptions(row) {
                const options = this.fields[row.field]?.options || {};
                return Object.entries(options).map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`).join('');
            },
            filterChoices(index) {
                const row = this.rows[index];
                const options = this.fields[row?.field]?.options || {};
                return Object.entries(options).map(([value, label]) => `<label class="table-filter-choice"><input type="checkbox" value="${escapeHtml(value)}"${row.values.includes(value) ? ' checked' : ''}> ${escapeHtml(label)}</label>`).join('');
            },
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
                return row.values.length > 2 ? selected.replace(':count', row.values.length) : row.values.map(value => options[value] ?? value).join(', ');
            },
            setPart(key) {
                const names = new Set(Object.values(this.parts).flatMap(Object.keys));
                this.setQuery(Object.fromEntries([ ...names ].map(name => [ name, this.parts[key]?.[name] ?? '' ])));
            },
            setQuery(values) {
                const params = new URLSearchParams(window.location.search);
                Object.entries(values).forEach(([name, value]) => value === '' || value == null ? params.delete(name) : params.set(name, value));
                window.history.replaceState(null, '', '?' + this.query(params));
                this.goToPage(1);
            },
            applyFilters() {
                this.goToPage(1);
            },
            query(params) {
                return params.toString().replace(/%2C/gi, ',').replace(/%21/g, '!');
            },
            param(field) {
                return this.fields[field]?.param || field;
            },
            goToPage(page) {
                const params = new URLSearchParams(window.location.search);
                Object.keys(this.fields).forEach(field => {
                    params.delete(this.param(field));
                    params.delete(`${this.param(field)}!`);
                });
                page > 1 ? params.set('page', page) : params.delete('page');
                const search = this.search.trim();
                search ? params.set('s', search) : params.delete('s');
                this.activeFilters().forEach(row => {
                    const name = this.param(row.field) + (row.operator === 'not' ? '!' : '');
                    const values = new Set([ ...params.get(name)?.split(',') || [], ...row.values ]);
                    params.set(name, [ ...values ].join(','));
                });
                window.history.replaceState(null, '', '?' + this.query(params));
                this.$ajax.post('tables/filter', Object.fromEntries(params), null, {
                    el: root
                }).then(() => {
                    this.resetAll(root);
                    [ '.table-body', '.table-header', '.table-head-data' ].map(selector => root.querySelector(selector)).forEach(el => el && root.__x.initialize(el));
                });
            }
        };
    });
    Youla.data('builder', () => (state = {}, valueOptions = {}) => {
        const defaultRule = () => ({
            location: Object.keys(valueOptions)[0] || 'post_type',
            operator: '==',
            value: ''
        });
        const TEXT_LIKE_TYPES = [ 'text', 'password', 'number' ];
        const CHOICE_TYPES = [ 'select', 'checkbox', 'radio' ];
        const blankField = type => ({
            type,
            label: '',
            name: '',
            required: false,
            instruction: '',
            placeholder: '',
            min: '',
            max: '',
            rows: '',
            options: ''
        });
        const slugifyName = label => String(label || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            '\'': '&#39;'
        }[char]));
        return {
            id: state.id || 0,
            title: state.title || '',
            status: state.status || 'active',
            valueOptions,
            groups: Array.isArray(state.groups) && state.groups.length ? state.groups : [ {
                rules: [ defaultRule() ]
            } ],
            fields: (Array.isArray(state.fields) ? state.fields : []).map(field => ({
                ...blankField(field.type || 'text'),
                ...field,
                required: !!(field.attributes && field.attributes.required),
                placeholder: field.attributes?.placeholder || '',
                min: field.attributes?.min ?? '',
                max: field.attributes?.max ?? '',
                rows: field.attributes?.rows ?? '',
                options: Object.entries(field.options || {}).map(([value, label]) => value === label ? value : `${value}:${label}`).join('\n')
            })),
            addGroup() {
                this.groups.push({
                    rules: [ defaultRule() ]
                });
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
            locationValueOptions(rule) {
                const options = this.valueOptions[rule.location] || {};
                return Object.entries(options).map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`).join('');
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
                [this.fields[index], this.fields[target]] = [ this.fields[target], this.fields[index] ];
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
            }
        };
    });
    Youla.data('navEditor', root => (state = {}) => {
        const maxDepth = state.maxDepth ?? 5;
        const fields = state.fields && typeof state.fields === 'object' ? state.fields : {};
        let lastKey = 0;
        const item = values => ({
            type: 'link',
            object: '',
            objectId: 0,
            ...Object.fromEntries(Object.entries(fields).map(([name, field]) => [ name, structuredClone(field.default ?? '') ])),
            original: '',
            depth: 0,
            ...values,
            key: ++lastKey
        });
        const serialize = items => JSON.stringify(items.map(({key, original, ...values}) => values));
        const blank = item(Object.fromEntries(Object.keys(fields).map(name => [ name, Array.isArray(fields[name].default) ? [] : '' ])));
        let saved = serialize(state.items || []);
        const data = {
            id: state.id || 0,
            title: state.title || '',
            locations: Array.isArray(state.locations) ? state.locations : [],
            items: (Array.isArray(state.items) ? state.items : []).map(values => item(values)),
            types: state.types || {},
            maxDepth,
            source: '',
            query: '',
            found: null,
            posts: {},
            link: {
                url: '',
                title: ''
            },
            active: blank,
            activeKey: 0,
            label(entry) {
                return entry.title || entry.original || entry.url;
            },
            select(index) {
                this.active = this.items[index] || blank;
                this.activeKey = this.active.key === blank.key ? 0 : this.active.key;
            },
            isFor(name) {
                const types = fields[name]?.types || [];
                return !this.activeKey || types.length === 0 || types.includes(this.active.type);
            },
            focusActive() {
                setTimeout(() => root.querySelector('.nav-editor-item.is-active .nav-editor-item-head')?.focus(), 50);
            },
            end(index) {
                let end = index + 1;
                while (end < this.items.length && this.items[end].depth > this.items[index].depth) {
                    end++;
                }
                return end;
            },
            previous(index) {
                const depth = this.items[index]?.depth;
                for (let i = index - 1; i >= 0; i--) {
                    if (this.items[i].depth <= depth) {
                        return this.items[i].depth === depth ? i : -1;
                    }
                }
                return -1;
            },
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
                const end = this.end(index);
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
                const end = this.end(index);
                for (let i = index; i < end; i++) {
                    items[i].depth += direction;
                }
                this.normalize(items);
            },
            remove(index) {
                const items = [ ...this.items ];
                const end = this.end(index);
                for (let i = index + 1; i < end; i++) {
                    items[i].depth--;
                }
                items.splice(index, 1);
                this.normalize(items);
                this.select(-1);
            },
            sort({from, count, to, depth}) {
                const items = [ ...this.items ];
                const block = items.splice(from, count);
                const shift = depth - block[0].depth;
                block.forEach(entry => entry.depth += shift);
                items.splice(to, 0, ...block);
                this.normalize(items);
            },
            openSource(key, el) {
                this.source = this.source === key ? '' : key;
                if (this.source === key && !this.posts[key]) {
                    this.$ajax.post('menus/posts', {
                        type: key
                    }, null, {
                        el
                    }).then(result => {
                        this.posts = {
                            ...this.posts,
                            [key]: Array.isArray(result?.posts) ? result.posts : []
                        };
                    }).catch(() => {});
                }
            },
            search(query) {
                this.query = query.trim();
                this.found = null;
                if (this.query === '') {
                    return;
                }
                this.$ajax.post('menus/posts', {
                    query: this.query
                }, null, {
                    el: this.$el?.closest('.field')
                }).then(result => {
                    this.found = Array.isArray(result?.posts) ? result.posts : [];
                }).catch(() => {});
            },
            add(values) {
                this.items = [ ...this.items, item(values) ];
                this.select(this.items.length - 1);
            },
            addPost(key, post) {
                this.add({
                    type: 'post',
                    object: key,
                    objectId: post.id,
                    original: post.title
                });
            },
            addLink() {
                const url = this.link.url.trim();
                if (url === '') {
                    return;
                }
                this.add({
                    url,
                    title: this.link.title.trim()
                });
                this.link = {
                    url: '',
                    title: ''
                };
            },
            save() {
                const items = serialize(this.items);
                this.$ajax.post(this.id ? 'menus/update' : 'menus/create', {
                    id: this.id,
                    title: this.title,
                    locations: JSON.stringify(this.locations),
                    items
                }).then(() => {
                    saved = items;
                }).catch(() => {});
            },
            searchInput: {
                '@input.delay.250ms'() {
                    this.search(this.$el.value);
                }
            },
            linkForm: {
                '@submit.prevent'() {
                    this.addLink();
                    this.$el.reset();
                }
            },
            linkUrl: {
                'u-prop': 'link.url'
            },
            linkTitle: {
                'u-prop': 'link.title'
            },
            results: {
                'u-show': 'query'
            },
            result: {
                'u-each': 'post in found || []',
                '@click': 'addPost(post.type, post)'
            },
            postTitle: {
                'u-text': 'post.title'
            },
            postType: {
                'u-text': 'types[post.type] || post.type'
            },
            noResults: {
                'u-show': 'found && found.length === 0'
            },
            sources: {
                'u-show': '! query || (found && found.length === 0)'
            },
            sourceTitle(key) {
                return {
                    '@click'() {
                        this.openSource(key, this.$el.closest('.accordion-item'));
                    },
                    ':class'() {
                        return this.source === key && 'is-active';
                    }
                };
            },
            sourcePanel(key) {
                return {
                    'u-collapse'() {
                        return this.source === key;
                    }
                };
            },
            sourcePost(key) {
                return {
                    'u-each': `post in posts[${JSON.stringify(key)}]`,
                    '@click': `addPost(${JSON.stringify(key)}, post)`
                };
            },
            sourceEmpty(key) {
                return {
                    'u-show'() {
                        return this.posts[key]?.length === 0;
                    }
                };
            },
            editor: {
                '@submit.prevent'() {
                    this.save();
                }
            },
            titleInput: {
                'u-prop': 'title'
            },
            list: {
                'u-sortable': '{ handle: \'.sortable-handle\', nested: true, indent: 24, maxDepth }',
                '@sort'(event) {
                    this.sort(event.detail);
                }
            },
            row: {
                'u-each': '(item, i) in items',
                ':hidden': 'false',
                ':style': '\'--depth: \' + item.depth',
                ':class': '{ \'is-active\': item.key === activeKey }'
            },
            rowHead: {
                '@click': 'select(i)',
                '@keydown.alt.up.prevent': 'select(i), move(i, -1), focusActive()',
                '@keydown.alt.down.prevent': 'select(i), move(i, 1), focusActive()',
                '@keydown.alt.left.prevent': 'select(i), indent(i, -1), focusActive()',
                '@keydown.alt.right.prevent': 'select(i), indent(i, 1), focusActive()'
            },
            rowIcon: {
                ':class': 'item.icon',
                'u-show': 'item.icon'
            },
            rowLabel: {
                'u-text': 'label(item)'
            },
            rowType: {
                'u-show': 'item.type === \'post\'',
                'u-text': 'types[item.object] || item.object'
            },
            rowLink: {
                'u-show': 'item.type === \'link\''
            },
            rowRemove: {
                '@click': 'remove(i)'
            },
            empty: {
                'u-show': 'items.length === 0'
            },
            panel: {
                ':class': 'activeKey && \'is-active\''
            },
            closer: {
                '@click'() {
                    this.select(-1);
                }
            },
            hint: {
                'u-show': '! activeKey'
            },
            field(name) {
                return {
                    'u-show'() {
                        return this.isFor(name);
                    }
                };
            },
            control(name) {
                const bindings = {
                    'u-prop': `active.${name}`,
                    ':disabled': '! activeKey'
                };
                if (this.$el.tagName === 'SELECT') {
                    bindings['u-select'] = function() {
                        return {
                            placeholderText: fields[name]?.placeholder || ''
                        };
                    };
                }
                if (name === 'title') {
                    bindings[':placeholder'] = function() {
                        return this.active.original || fields[name]?.placeholder || '';
                    };
                }
                return bindings;
            },
            location: {
                'u-prop': 'locations'
            },
            deleter: {
                '@click'() {
                    this.$dialog.open('tmpl-menu-delete', {
                        id: this.id,
                        title: this.$el.textContent.trim()
                    });
                }
            }
        };
        window.addEventListener('beforeunload', event => {
            if (serialize(data.items) !== saved) {
                event.preventDefault();
            }
        });
        return data;
    });
    Youla.data('stream', root => ({
        error: null,
        canvas: null,
        second: 0,
        photo: '',
        videoRef: {
            'u-ref': 'video'
        },
        imageRef: {
            'u-ref': 'image'
        },
        canvasRef: {
            'u-ref': 'canvas'
        },
        get refs() {
            return {
                video: root.querySelector('[u-ref="video"]'),
                image: root.querySelector('[u-ref="image"]'),
                canvas: root.querySelector('[u-ref="canvas"]')
            };
        },
        check() {
            const {video, image} = this.refs;
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
                video.srcObject = video._x_stream = await navigator.mediaDevices.getUserMedia({
                    video: true
                });
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
            const {video, image} = this.refs;
            let imageStyles = window.getComputedStyle(image), targetRatio = parseInt(imageStyles.width, 10) / parseInt(imageStyles.height, 10);
            let videoWidth = video.videoWidth, videoHeight = video.videoHeight, videoRatio = videoWidth / videoHeight;
            let sWidth, sHeight;
            if (videoRatio > targetRatio) {
                sHeight = videoHeight;
                sWidth = videoHeight * targetRatio;
            } else {
                sWidth = videoWidth;
                sHeight = videoWidth / targetRatio;
            }
            let sx = (videoWidth - sWidth) / 2, sy = (videoHeight - sHeight) / 2;
            canvas.width = sWidth;
            canvas.height = sHeight;
            let ctx = canvas.getContext('2d');
            ctx.drawImage(video, sx, sy, sWidth, sHeight, 0, 0, sWidth, sHeight);
            let imageData = canvas.toDataURL('image/png');
            if (imageData) {
                image.src = imageData;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            return imageData;
        },
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
            }, 1e3);
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
        }
    }));
    Youla.data('search', () => ({
        currentIdx: -1,
        links: [],
        query: '',
        searched: '',
        find() {
            const query = this.query.trim();
            if (query.length < 2) {
                this.links = [];
                this.searched = '';
                return;
            }
            const show = links => {
                if (query === this.query.trim()) {
                    this.links = links;
                    this.searched = query;
                    this.currentIdx = -1;
                }
            };
            this.$ajax.post('search/find', {
                query
            }).then(result => show(Array.isArray(result?.links) ? result.links : [])).catch(() => show([]));
        },
        input: {
            '@keydown.prevent.window.ctrl.k'() {
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
            }
        },
        results: {
            'u-show': 'links.length > 0'
        },
        row: {
            'u-each': '(link, i) in links',
            ':class': 'link.url && i === currentIdx && \'is-active\''
        },
        anchor: {
            ':href': 'link.url',
            'u-show': 'link.url',
            'u-text': 'link.text'
        },
        heading: {
            'u-show': '!link.url',
            'u-text': 'link.text'
        },
        idle: {
            'u-show': '!links.length && !searched.trim()'
        },
        empty: {
            'u-show': '!links.length && searched.trim() !== \'\''
        }
    }));
    Youla.data('slug', root => ({
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
        generate(text) {
            return String(text).trim() === '' ? Promise.resolve('') : this.$ajax.post('slugs/generate', {
                text
            }, null, {
                clearErrors: false
            });
        },
        follow() {
            const text = this.source()?.value || '';
            return this.generate(text).then(slug => {
                if (this.synced && document.activeElement !== this.$refs.slug) {
                    this.value = slug;
                }
            });
        },
        touch() {
            setTimeout(() => this.$refs.slug?.dispatchEvent(new Event('change', {
                bubbles: true
            })));
        },
        line: {
            '@load': 'synced && follow()',
            '@input.document.delay.500ms'(e) {
                if (this.synced && e.target === this.source()) {
                    this.follow();
                }
            },
            ':data-url'() {
                const url = this.base + this.value + this.suffix;
                root.dispatchEvent(new CustomEvent('slug-url', {
                    detail: url,
                    bubbles: true
                }));
                return url;
            },
            '@change.document'(e) {
                if (this.parent && e.target.name === this.parent && e.target.form === root.closest('form')) {
                    this.base = this.bases[e.target.value] ?? e.target.selectedOptions?.[0]?.dataset.base ?? this.base;
                }
            }
        },
        input: {
            'u-ref': 'slug',
            ':value'() {
                return this.value;
            },
            '@focus'() {
                this.before = {
                    value: this.value,
                    synced: this.synced
                };
            },
            '@input.delay.500ms'(e) {
                const el = e.target;
                if (!e.isTrusted) {
                    this.value = el.value;
                    this.$dirty.remove(el.form);
                    return;
                }
                if (document.activeElement !== el) {
                    return;
                }
                const text = el.value;
                this.value = text;
                this.synced = false;
                this.generate(text).then(slug => {
                    if (el.value !== text) {
                        return;
                    }
                    const next = slug && document.activeElement === el && /[^\p{L}\p{M}\p{N}_]$/u.test(text) ? `${slug}-` : slug;
                    if (next !== text) {
                        const tail = text.length - el.selectionEnd;
                        el.value = next;
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
                    this.value = this.before.value;
                    this.synced = this.before.synced;
                    this.touch();
                }
                this.$el.blur();
            },
            '@blur'() {
                const typed = this.$el.value;
                if (typed !== this.value) {
                    this.value = typed;
                    this.synced = false;
                }
                this.before = null;
                this.generate(typed).then(slug => {
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
            }
        }
    }));
    Youla.data('postDetails', root => (saved = '') => ({
        status: saved,
        self: {
            '@change'() {
                this.schedule(this.$event.target);
            },
            '@reset.document'() {
                this.restore(this.$event.target);
            }
        },
        schedule(input) {
            if (input.name !== 'created_at' || !input.value) {
                return;
            }
            const ahead = new Date(input.value) > new Date;
            if (this.status === 'publish' && ahead) {
                this.status = 'future';
            } else if (this.status === 'future' && !ahead) {
                this.status = 'publish';
            }
        },
        restore(form) {
            if (form === root.closest('form')) {
                this.status = saved;
            }
        }
    }));
    Youla.data('tab', () => ({
        active: null,
        button(id) {
            return {
                ':class'() {
                    this.active ??= this.$el.classList.contains('is-active') ? id : null;
                    return {
                        'is-active': this.active === id
                    };
                },
                '@click'() {
                    this.active = id;
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', id);
                    window.history.pushState({}, '', url);
                }
            };
        },
        content(id) {
            return {
                'u-show'() {
                    this.active ??= this.$el.hidden ? null : id;
                    return this.active === id;
                }
            };
        }
    }));
    const dirtyStates = new Map;
    window.addEventListener('click', e => {
        if (document.body.classList.contains('is-unsaved') && e.target.closest('a[href]:not([target="_blank" i])')) {
            e.preventDefault();
            document.body.classList.add('is-shake');
            setTimeout(() => document.body.classList.remove('is-shake'), 500);
        }
    }, true);
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
                setTimeout(() => {
                    dirtyStates.set(form, serialize(form));
                    form.addEventListener('input', sync);
                    form.addEventListener('change', sync);
                    form.addEventListener('reset', () => setTimeout(() => this.remove(form), 0));
                }, 50);
            },
            remove(form) {
                if (!dirtyStates.has(form)) {
                    return;
                }
                dirtyStates.set(form, serialize(form));
                sync();
            }
        };
    });
    Youla.method('copy', (e, el) => (subject, classes) => {
        window.navigator.clipboard.writeText(subject).then(() => {
            const toggled = classes || [ 'ph-copy', 'ph-check' ];
            const classesToggle = () => toggled.forEach(s => el.classList.toggle(s));
            classesToggle();
            setTimeout(classesToggle, 1e3);
        });
    });
    Youla.method('spin', (e, el) => direction => {
        const input = el.parentElement.querySelector('input[type="number"]');
        if (input.step === 'any') {
            input.value = Number(input.value || 0) + direction;
        } else {
            direction < 0 ? input.stepDown() : input.stepUp();
        }
        input.dispatchEvent(new Event('input', {
            bubbles: true
        }));
    });
    Youla.method('fill', (e, el) => value => {
        const input = el.closest('.field')?.querySelector('input, textarea');
        if (!input) {
            return;
        }
        input.value = value;
        input.dispatchEvent(new Event('input', {
            bubbles: true
        }));
        input.dispatchEvent(new Event('change', {
            bubbles: true
        }));
        input.focus();
    });
    Youla.directive('highlight', (el, output, {modifiers}) => {
        if (el._x_highlighted) {
            return;
        }
        el._x_highlighted = true;
        const lang = modifiers[0] || 'html';
        const wrapper = document.createElement('code');
        wrapper.className = `language-${lang}`;
        wrapper.append(...el.childNodes);
        el.classList.add('line-numbers');
        el.setAttribute('data-lang', lang.toUpperCase());
        el.replaceChildren(wrapper);
    });
    Youla.directive('noautofill', el => {
        if (el._x_noautofill) {
            return;
        }
        el._x_noautofill = true;
        const lock = () => el.readOnly = true;
        lock();
        el.addEventListener('focus', () => requestAnimationFrame(() => el.readOnly = false));
        el.addEventListener('blur', lock);
    });
    Youla.directive('sticky', el => {
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
        const measure = () => {
            const root = getComputedStyle(document.documentElement);
            const style = getComputedStyle(parent);
            maxTop = (parseFloat(root.scrollPaddingTop) || 0) + parseFloat(style.paddingTop);
            bottom = (parseFloat(root.scrollPaddingBottom) || 0) + parseFloat(style.paddingBottom);
        };
        measure();
        el.style.position = 'sticky';
        el.style.top = `${maxTop}px`;
        let lastScroll = window.scrollY;
        let lastTop = el.getBoundingClientRect().top;
        const reposition = () => {
            const delta = window.scrollY - lastScroll;
            const minTop = Math.min(maxTop, window.innerHeight - el.offsetHeight - bottom);
            lastScroll = window.scrollY;
            el.style.top = `${Math.min(maxTop, Math.max(minTop, lastTop - delta))}px`;
            lastTop = el.getBoundingClientRect().top;
        };
        window.addEventListener('scroll', reposition, {
            passive: true
        });
        window.addEventListener('load', reposition);
        window.addEventListener('resize', () => {
            measure();
            reposition();
        });
    });
    (() => {
        const THRESHOLD = 4;
        const EDGE = 48;
        const SPEED = 16;
        const LANDING = 150;
        const itemsOf = list => [ ...list.children ].filter(el => !el.hasAttribute('u-each') && !el.hidden && el.tagName !== 'TEMPLATE');
        const depthOf = el => +(el.style.getPropertyValue('--depth') || 0);
        function scrollerOf(el) {
            for (let node = el.parentElement; node && node !== document.body; node = node.parentElement) {
                const {overflowY} = getComputedStyle(node);
                if ((overflowY === 'auto' || overflowY === 'scroll') && node.scrollHeight > node.clientHeight) {
                    return node;
                }
            }
            return document.scrollingElement;
        }
        function start(list, event) {
            const options = list._x_sortable;
            const row = itemsOf(list).find(item => item.contains(event.target));
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
                    const swallow = event => event.stopPropagation();
                    window.addEventListener('click', swallow, {
                        capture: true,
                        once: true
                    });
                    setTimeout(() => window.removeEventListener('click', swallow, {
                        capture: true
                    }), 0);
                    finish(drag);
                }
            };
            document.addEventListener('pointermove', move, {
                passive: false
            });
            document.addEventListener('pointerup', stop);
            document.addEventListener('pointercancel', stop);
        }
        function begin(list, row, startX, startY) {
            const options = list._x_sortable;
            const rows = itemsOf(list);
            const from = rows.indexOf(row);
            let count = 1;
            while (options.nested && from + count < rows.length && depthOf(rows[from + count]) > depthOf(row)) {
                count++;
            }
            const scroller = scrollerOf(list);
            const offset = scroller.scrollTop;
            const rects = rows.map(el => {
                const rect = el.getBoundingClientRect();
                return {
                    top: rect.top + offset,
                    bottom: rect.bottom + offset
                };
            });
            const block = rows.slice(from, from + count);
            const others = rows.filter(el => !block.includes(el)).map(el => ({
                el,
                rect: rects[rows.indexOf(el)]
            }));
            const gap = rows.length > 1 ? Math.max(0, rects[1].top - rects[0].bottom) : 0;
            const top = rects[from].top;
            const bottom = rects[from + count - 1].bottom;
            const deepest = Math.max(...block.map(depthOf)) - depthOf(row);
            list.classList.add('is-sorting');
            block.forEach(el => el.classList.add('is-dragging'));
            const drag = {
                list,
                rows,
                block,
                others,
                from,
                count,
                top,
                bottom,
                scroller,
                startX,
                startY,
                offset,
                shift: bottom - top + gap,
                depth: depthOf(row),
                maxDepth: Math.max(depthOf(row), (options.maxDepth ?? Infinity) - deepest),
                to: from,
                pointer: null
            };
            autoscroll(drag);
            return drag;
        }
        function update(drag) {
            const {list, block, others, top, bottom, scroller, pointer} = drag;
            const options = list._x_sortable;
            const dx = pointer.clientX - drag.startX;
            const dy = pointer.clientY - drag.startY + scroller.scrollTop - drag.offset;
            const center = (top + bottom) / 2 + dy;
            const to = others.filter(({rect}) => (rect.top + rect.bottom) / 2 < center).length;
            others.forEach(({el}, index) => {
                const before = drag.rows.indexOf(el) < drag.from;
                const y = before && index >= to ? drag.shift : !before && index < to ? -drag.shift : 0;
                el.style.transform = y ? `translateY(${y}px)` : '';
            });
            let depth = depthOf(drag.rows[drag.from]);
            if (options.nested) {
                const above = others[to - 1]?.el;
                const below = others[to]?.el;
                const limit = Math.min(above ? depthOf(above) + 1 : 0, drag.maxDepth);
                const lower = below ? Math.min(depthOf(below), limit) : 0;
                depth = Math.max(lower, Math.min(depth + Math.round(dx / (options.indent || 24)), limit));
            }
            drag.to = to;
            drag.depth = depth;
            drag.x = (depth - depthOf(drag.rows[drag.from])) * (options.indent || 24);
            block.forEach(el => el.style.transform = `translate(${drag.x}px, ${dy}px)`);
        }
        function autoscroll(drag) {
            const step = () => {
                if (drag.done) {
                    return;
                }
                if (drag.pointer) {
                    const area = drag.scroller === document.scrollingElement ? {
                        top: 0,
                        bottom: window.innerHeight
                    } : drag.scroller.getBoundingClientRect();
                    const {clientY} = drag.pointer;
                    const speed = clientY < area.top + EDGE ? -SPEED * (1 - (clientY - area.top) / EDGE) : clientY > area.bottom - EDGE ? SPEED * (1 - (area.bottom - clientY) / EDGE) : 0;
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
            const {list, rows, block, others, from, count, to, top, bottom} = drag;
            drag.done = true;
            const landing = to < from ? others[to].rect.top - top : to > from ? others[to - 1].rect.bottom - bottom : 0;
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            block.forEach(el => {
                el.style.transition = reduced ? '' : `transform ${LANDING}ms ease`;
                el.style.transform = `translate(${drag.x || 0}px, ${landing}px)`;
            });
            setTimeout(() => {
                const depth = drag.depth;
                const shift = depth - depthOf(rows[from]);
                const changed = to !== from || shift !== 0;
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
                    el.style.transform = '';
                    el.style.transition = '';
                });
                if (changed) {
                    list.dispatchEvent(new CustomEvent('sort', {
                        detail: {
                            from,
                            count,
                            to,
                            depth
                        },
                        bubbles: true
                    }));
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
    Youla.directive('collapse', (el, output) => {
        const isOpen = !!output;
        const duration = 200;
        const props = [ 'height', 'paddingTop', 'paddingBottom', 'marginTop', 'marginBottom' ];
        if (el._x_collapse === undefined) {
            el._x_collapse = isOpen;
            el.hidden = !isOpen;
            return;
        }
        if (el._x_collapse === isOpen) {
            return;
        }
        el._x_collapse = isOpen;
        el.hidden = false;
        el.style.overflow = 'hidden';
        if (isOpen) {
            el.style.display = 'block';
        }
        const from = Object.fromEntries(props.map(prop => [ prop, parseFloat(getComputedStyle(el)[prop]) ]));
        let start;
        function step(timestamp) {
            start ??= timestamp;
            const elapsed = Math.min(timestamp - start, duration);
            const ratio = isOpen ? elapsed / duration : 1 - elapsed / duration;
            props.forEach(prop => el.style[prop] = `${from[prop] * ratio}px`);
            if (elapsed < duration) {
                requestAnimationFrame(step);
            } else {
                el.style.display = isOpen ? '' : 'none';
                [ ...props, 'overflow' ].forEach(prop => el.style[prop] = '');
            }
        }
        requestAnimationFrame(step);
    });
    Youla.directive('progress', (el, output, {modifiers, duration, expression}) => {
        const [rawFrom = 0, rawTo = 100] = modifiers;
        const from = parseInt(rawFrom);
        const bound = expression !== '' && !isNaN(parseFloat(output));
        const to = bound ? parseFloat(output) : parseInt(rawTo);
        if (isNaN(from) || isNaN(to)) {
            console.warn('Youla.js: "u-progress" requires numeric from/to modifiers as percentages (or a numeric bound value), e.g. u-progress.20.80.600ms.');
            return;
        }
        const start = Math.min(Math.max(from, 0), 100);
        const end = Math.min(Math.max(to, 0), 100);
        const transitionDuration = duration ? `${duration.value}${duration.unit}` : '0ms';
        const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const apply = (percent, animate) => {
            if (animate && !reducedMotion()) {
                el.style.setProperty('--expansa-progress-transition', `width ${transitionDuration}`);
            }
            el.style.setProperty('--expansa-progress', `${percent}%`);
        };
        if (el._x_progress?.revealed) {
            el._x_progress.end = end;
            apply(end, true);
            return;
        }
        if (el._x_progress) {
            el._x_progress.end = end;
            return;
        }
        el._x_progress = {
            revealed: false,
            end
        };
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