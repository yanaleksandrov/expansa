(function() {
    document.addEventListener("youla:init", () => {
        (() => {
            function hasUData(el) {
                return [ ...el.attributes ].some(({name: name}) => name === "u-data" || name.startsWith("u-data."));
            }
            function closestComponent(el) {
                while (el && !hasUData(el)) {
                    el = el.parentElement;
                }
                return el ? el.__x : null;
            }
            const REQUIRED_FIELDS_SELECTOR = "input[required], select[required], textarea[required]";
            Youla.directive("step", (el, output, attribute, component) => {
                const wizard = component.data;
                const step = wizard.getStep(el);
                const required = attribute.modifiers.includes("required");
                if (required && !el._x_stepRequiredBound) {
                    el._x_stepRequiredBound = true;
                    el.querySelectorAll(REQUIRED_FIELDS_SELECTOR).forEach(field => {
                        [ "input", "change" ].forEach(event => field.addEventListener(event, () => component.refresh(el)));
                    });
                }
                let isComplete = required && attribute.expression.trim() === "" ? true : !!output;
                if (required) {
                    isComplete = isComplete && [ ...el.querySelectorAll(REQUIRED_FIELDS_SELECTOR) ].every(field => field.checkValidity());
                }
                if (step.isComplete !== isComplete) {
                    step.isComplete = isComplete;
                }
            });
            Youla.data("step", () => ({
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
                        total: total,
                        complete: complete,
                        current: current,
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
                    const expression = step?.el.getAttribute("u-step:action");
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
                            el: el,
                            isComplete: true
                        }) - 1;
                        this.render();
                    }
                    return this.steps[index];
                }
            }));
        })();
        (() => {
            Youla.variable("notice", () => document.querySelector('[u-data="notice"]')?.__x?.data);
            Youla.data("notice", () => ({
                items: {},
                duration: 7e3,
                hovering: false,
                info(message, duration) {
                    this.add(message, "info", duration);
                },
                success(message, duration) {
                    this.add(message, "success", duration);
                },
                warning(message, duration) {
                    this.add(message, "warning", duration);
                },
                error(message, duration) {
                    this.add(message, "error", duration);
                },
                loading(message, duration) {
                    this.add(message, "loading", duration);
                },
                pause() {
                    this.hovering = true;
                    Object.values(this.items).forEach(item => {
                        if (item.timer) {
                            clearTimeout(item.timer);
                            item.timer = null;
                            item.remaining = Math.max(0, item.remaining - (Date.now() - item.startedAt));
                        }
                    });
                },
                resume() {
                    this.hovering = false;
                    Object.keys(this.items).forEach(id => this.schedule(id));
                },
                schedule(id) {
                    let item = this.items[id];
                    if (item && !item.timer && item.duration) {
                        item.startedAt = Date.now();
                        item.timer = setTimeout(() => this.close(id), item.remaining);
                    }
                },
                elapsed(item) {
                    return item.duration - item.remaining + (item.timer ? Date.now() - item.startedAt : 0);
                },
                close(id) {
                    let item = this.items[id];
                    if (typeof item !== "undefined") {
                        clearTimeout(item.timer);
                        this.items = {
                            ...this.items,
                            [id]: {
                                ...item,
                                selectors: [ ...item.selectors, "hide" ]
                            }
                        };
                        setTimeout(() => {
                            let {[id]: omit, ...rest} = this.items;
                            this.items = rest;
                        }, 1e3);
                    }
                },
                add(message, type, duration) {
                    if (message) {
                        let timestamp = Date.now();
                        if (duration === "auto") {
                            duration = Math.max(message.length * 70, 1500);
                        } else if (duration === void 0) {
                            duration = this.duration;
                        }
                        this.items = {
                            ...this.items,
                            [timestamp]: {
                                message: message,
                                closable: true,
                                selectors: [ type || "info" ],
                                duration: duration,
                                remaining: duration,
                                startedAt: Date.now(),
                                timer: null,
                                classes() {
                                    return this.selectors.map(x => "notice__item--" + x).join(" ");
                                }
                            }
                        };
                        if (!this.hovering) {
                            this.schedule(timestamp);
                        }
                    }
                }
            }));
        })();
        (() => {
            Youla.variable("dialog", () => document.querySelector('[u-data="dialog"]')?.__x?.data);
            const searchParamsHandler = (param, value, isRemove) => {
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);
                if (isRemove) {
                    params.delete(param);
                } else {
                    params.set(param, value);
                }
                url.search = params.toString();
                window.history.replaceState({}, "", url.toString());
            };
            let uid = 0;
            let scrollY = 0;
            function lockScroll() {
                scrollY = window.scrollY;
                Object.assign(document.body.style, {
                    position: "fixed",
                    top: `-${scrollY}px`,
                    width: "100%",
                    overflow: "hidden"
                });
            }
            function unlockScroll() {
                Object.assign(document.body.style, {
                    position: "",
                    top: "",
                    width: "",
                    overflow: ""
                });
                window.scrollTo({
                    top: scrollY,
                    left: 0,
                    behavior: "instant"
                });
            }
            Youla.data("dialog", root => ({
                stack: [],
                open(templateID, data = {}) {
                    setTimeout(() => {
                        let template = document.getElementById(templateID);
                        if (!template) {
                            return;
                        }
                        const isBase = this.stack.length === 0;
                        this.stack.push({
                            id: ++uid,
                            content: template.innerHTML,
                            ...data
                        });
                        if (isBase) {
                            lockScroll();
                        }
                        root.dispatchEvent(new Event("open", {
                            bubbles: true
                        }));
                        if (isBase) {
                            searchParamsHandler("dialog", templateID, false);
                        }
                    }, 25);
                },
                close(id) {
                    const target = id ?? this.stack.at(-1)?.id;
                    this.stack = this.stack.filter(dialog => dialog.id !== target);
                    root.dispatchEvent(new Event("close", {
                        bubbles: true
                    }));
                    if (this.stack.length === 0) {
                        unlockScroll();
                        searchParamsHandler("dialog", null, true);
                    }
                },
                clear() {
                    [ ...this.stack ].forEach(entry => this.close(entry.id));
                },
                async init(templateID, callback) {
                    const params = new URLSearchParams(window.location.search);
                    if (templateID && params.get("dialog") === templateID && callback) {
                        const data = await callback();
                        if (data) {
                            this.open(templateID, data);
                        }
                    }
                }
            }));
        })();
        Youla.data("password", () => ({
            value: "",
            visible: false,
            progress: 0,
            labels: [ "Слишком слабый", "Слабый", "Средний", "Хороший", "Отличный" ],
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
                lowercase: "abcdefghijklmnopqrstuvwxyz",
                uppercase: "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                special: "!@#$%^&*(){|}~",
                digit: "0123456789"
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
                    value = this.value = value.replace(/\s/g, "");
                }
                let matchCount = 0;
                let totalWeight = Object.keys(this.charsets).reduce((sum, type) => sum + this.min[type], 0) + 1;
                for (const type in this.charsets) {
                    let charsetRegex = new RegExp(`[${this.charsets[type]}]`, "g");
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
                let pool = Object.values(this.charsets).join("");
                let password = "";
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
                let array = password.split("");
                for (let i = array.length - 1; i > 0; i--) {
                    let j = Math.floor(Math.random() * (i + 1));
                    [array[i], array[j]] = [ array[j], array[i] ];
                }
                return array.join("");
            }
        }));
        Youla.data("avatar", () => ({
            name: "",
            image: "",
            field: {
                "u-prop": "name"
            },
            picture: {
                ":title": "name",
                ":style": "image && `background-image:url(${image})`"
            },
            initials: {
                "u-show": "!image",
                "u-text"() {
                    return this.name.trim().split(/\s+/).map(word => word[0]).slice(0, 2).join("").toUpperCase();
                }
            },
            uploader: {
                "@change"() {
                    let file = this.$event.target.files[0];
                    if (file) {
                        let reader = new FileReader;
                        reader.onload = e => this.image = e.target.result;
                        reader.readAsDataURL(file);
                    }
                }
            },
            remover: {
                "u-show": "image",
                "@click"() {
                    let input = this.$root.querySelector('input[type="file"]');
                    if (input) {
                        input.value = "";
                    }
                    this.image = "";
                }
            }
        }));
        Youla.data("table", () => ({
            anchor: null,
            trigger: {
                "@change": "selectAll($el, $root)"
            },
            item: {
                "@click": "selectItem($el, $root, $event)"
            },
            items(root) {
                return [ ...root.querySelectorAll('[u-bind~="item"]') ];
            },
            selectAll(el, root) {
                this.items(root).forEach(input => input.checked = el.checked);
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
            }
        }));
        Youla.data("builder", () => ({
            default: {
                field: "post",
                operator: "===",
                value: ""
            },
            groups: [],
            addGroup() {
                this.groups.push({
                    rules: [ {
                        ...this.default
                    } ]
                });
            },
            removeGroup(index) {
                this.groups.splice(index, 1);
            },
            addRule(key) {
                this.groups[key].rules.push({
                    ...this.default
                });
            },
            removeRule(key, index) {
                this.groups[key].rules.splice(index, 1);
            },
            submit() {
                console.log(JSON.parse(JSON.stringify(this.groups)));
            }
        }));
        Youla.data("stream", root => ({
            error: null,
            canvas: null,
            videoRef: {
                "u-ref": "video"
            },
            imageRef: {
                "u-ref": "image"
            },
            canvasRef: {
                "u-ref": "canvas"
            },
            get refs() {
                return {
                    video: root.querySelector('[u-ref="video"]'),
                    image: root.querySelector('[u-ref="image"]'),
                    canvas: root.querySelector('[u-ref="canvas"]')
                };
            },
            check() {
                const {video: video, image: image} = this.refs;
                if (!video) {
                    console.error("Video for selfie preview is undefined");
                    return false;
                }
                if (!image) {
                    console.error("Image for output selfie is undefined");
                    return false;
                }
                return true;
            },
            getCanvas() {
                return this.refs.canvas || (this.canvas || (this.canvas = document.createElement("canvas")));
            },
            isVisible(element) {
                const styles = window.getComputedStyle(element);
                if (styles) {
                    return !(styles.visibility === "hidden" || styles.display === "none" || parseFloat(styles.opacity) === 0);
                }
                return false;
            },
            async requestStream(video) {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    this.error = "unsupported";
                    return;
                }
                try {
                    video.srcObject = video._x_stream = await navigator.mediaDevices.getUserMedia({
                        video: true
                    });
                    this.error = null;
                } catch (error) {
                    this.error = error.name === "NotAllowedError" || error.name === "SecurityError" ? "denied" : "unavailable";
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
                const {video: video, image: image} = this.refs;
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
                let ctx = canvas.getContext("2d");
                ctx.drawImage(video, sx, sy, sWidth, sHeight, 0, 0, sWidth, sHeight);
                let imageData = canvas.toDataURL("image/png");
                if (imageData) {
                    image.src = imageData;
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
                return imageData;
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
        Youla.data("search", () => ({
            currentIdx: -1,
            links: [],
            wrapper: {
                "@click.outside"() {
                    this.$el.removeAttribute("open");
                },
                "@keydown.escape"() {
                    this.$el.removeAttribute("open");
                },
                "@keydown.prevent.window.ctrl.k"() {
                    this.$refs.searchButton.click();
                }
            },
            button: {
                "u-ref": "searchButton",
                "@click"() {
                    setTimeout(() => this.$refs.searchInput.focus());
                }
            },
            input: {
                "u-ref": "searchInput",
                "@keydown.up"() {
                    this.currentIdx = this.currentIdx <= 0 ? this.links.length - 1 : this.currentIdx - 1;
                    if (!this.links[this.currentIdx]?.url && this.currentIdx === 0) {
                        this.currentIdx = this.links.length - 1;
                    }
                },
                "@keydown.down"() {
                    this.currentIdx = this.currentIdx >= this.links.length - 1 ? 0 : this.currentIdx + 1;
                    if (!this.links[this.currentIdx]?.url) {
                        this.currentIdx++;
                    }
                },
                "@keydown.enter"() {
                    this.links[this.currentIdx] && (window.location.href = this.links[this.currentIdx].url);
                }
            }
        }));
        Youla.data("tab", root => ({
            tab: new URLSearchParams(window.location.search).get("tab") || root.dataset.tab || null,
            tabButton(id) {
                return {
                    ":class"() {
                        return this.tab === id ? "active" : "";
                    },
                    "@click"() {
                        this.tab = id;
                        const url = new URL(window.location.href);
                        url.searchParams.set("tab", id);
                        window.history.pushState({}, "", url);
                    }
                };
            },
            tabContent(id) {
                return {
                    "u-show"() {
                        return this.tab === id;
                    }
                };
            }
        }));
        Youla.variable("dirty", () => {
            const serialize = form => JSON.stringify(Object.fromEntries(new FormData(form).entries()));
            const sync = () => {
                const isDirty = [ ...document.querySelectorAll("form[data-dirty-watch]") ].some(form => form.dataset.initialState !== serialize(form));
                document.body.classList.toggle("is-unsaved", isDirty);
            };
            return {
                watch(form) {
                    if (!(form instanceof HTMLFormElement) || form.dataset.dirtyWatch !== undefined) {
                        return;
                    }
                    form.dataset.dirtyWatch = "";
                    if (document.body.dataset.dirtyBound === undefined) {
                        document.body.dataset.dirtyBound = "";
                        window.addEventListener("click", e => {
                            if (document.body.classList.contains("is-unsaved") && e.target.closest("a[href]")) {
                                e.preventDefault();
                                document.body.classList.add("is-shake");
                                setTimeout(() => document.body.classList.remove("is-shake"), 500);
                            }
                        }, true);
                    }
                    setTimeout(() => {
                        form.dataset.initialState = serialize(form);
                        form.addEventListener("input", sync);
                        form.addEventListener("change", sync);
                        form.addEventListener("reset", () => setTimeout(() => {
                            form.dataset.initialState = serialize(form);
                            sync();
                        }, 0));
                    }, 50);
                },
                remove(form) {
                    if (!(form instanceof HTMLFormElement)) {
                        return;
                    }
                    form.dataset.initialState = serialize(form);
                    sync();
                }
            };
        });
        Youla.method("copy", (e, el) => (subject, classes) => {
            window.navigator.clipboard.writeText(subject).then(() => {
                const classes = classes || [ "ph-copy", "ph-check" ];
                const classesToggle = () => classes.forEach(s => el.classList.toggle(s));
                classesToggle();
                setTimeout(classesToggle, 1e3);
            });
        });
        Youla.method("safe", () => ({
            slug(value) {
                return value.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^\p{L}\p{N}\s-]/gu, "").trim().replace(/\s+/g, "-").replace(/-+/g, "-").toLowerCase();
            }
        }));
        Youla.directive("highlight", (el, output, {modifiers: modifiers}) => {
            if (el._x_highlighted) {
                return;
            }
            el._x_highlighted = true;
            const lang = modifiers[0] || "html";
            const wrapper = document.createElement("code");
            wrapper.className = `language-${lang}`;
            wrapper.append(...el.childNodes);
            el.classList.add("line-numbers");
            el.setAttribute("data-lang", lang.toUpperCase());
            el.replaceChildren(wrapper);
        });
        Youla.directive("noautofill", el => {
            if (el._x_noautofill) {
                return;
            }
            el._x_noautofill = true;
            const lock = () => el.readOnly = true;
            lock();
            el.addEventListener("focus", () => requestAnimationFrame(() => el.readOnly = false));
            el.addEventListener("blur", lock);
        });
        Youla.directive("sticky", el => {
            if (el._x_sticky) {
                return;
            }
            el._x_sticky = true;
            const parent = el.parentElement;
            if (getComputedStyle(parent).position !== "relative") {
                console.warn('Youla.js: "u-sticky" requires its parent to have position: relative.');
                return;
            }
            const paddingTop = parseInt(getComputedStyle(parent).paddingTop) + 42;
            const paddingBottom = parseInt(getComputedStyle(parent).paddingBottom);
            let top = paddingTop;
            let lastScroll = window.scrollY;
            const reposition = () => {
                const rect = el.getBoundingClientRect();
                const overflow = rect.height - window.innerHeight;
                const delta = window.scrollY - lastScroll;
                lastScroll = window.scrollY;
                if (overflow <= 0 || rect.top > top) {
                    return;
                }
                top = Math.min(paddingTop, Math.max(-overflow - paddingBottom, top - delta));
                el.style.top = `${top}px`;
            };
            el.style.position = "sticky";
            el.style.top = `${paddingTop}px`;
            [ "load", "scroll", "resize" ].forEach(event => window.addEventListener(event, reposition));
        });
        Youla.directive("collapse", (el, output) => {
            const isOpen = !!output;
            const duration = 200;
            const props = [ "height", "paddingTop", "paddingBottom", "marginTop", "marginBottom" ];
            el.style.overflow = "hidden";
            if (isOpen) {
                el.style.display = "block";
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
                    if (!isOpen) {
                        el.style.display = "none";
                    }
                    [ ...props, "overflow" ].forEach(prop => el.style[prop] = "");
                }
            }
            requestAnimationFrame(step);
        });
        Youla.directive("textarea", (el, output) => {
            if (el.tagName !== "TEXTAREA" || el._x_textarea) {
                return;
            }
            el._x_textarea = true;
            el.addEventListener("input", () => {
                const maxRows = parseInt(output) || 99;
                if (el.value.split(/\r\n|\r|\n/).length > maxRows) {
                    return;
                }
                const border = parseInt(getComputedStyle(el).borderWidth) * 4;
                el.style.height = "auto";
                el.style.height = `${el.scrollHeight + border + 4}px`;
            });
        });
        Youla.directive("progress", (el, output, {modifiers: modifiers, duration: duration, expression: expression}) => {
            const [rawFrom = 0, rawTo = 100] = modifiers;
            const from = parseInt(rawFrom);
            const bound = expression !== "" && !isNaN(parseFloat(output));
            const to = bound ? parseFloat(output) : parseInt(rawTo);
            if (isNaN(from) || isNaN(to)) {
                console.warn('Youla.js: "u-progress" requires numeric from/to modifiers as percentages (or a numeric bound value), e.g. u-progress.20.80.600ms.');
                return;
            }
            const start = Math.min(Math.max(from, 0), 100);
            const end = Math.min(Math.max(to, 0), 100);
            const transitionDuration = duration ? `${duration.value}${duration.unit}` : "0ms";
            const reducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            const apply = (percent, animate) => {
                if (animate && !reducedMotion()) {
                    el.style.setProperty("--youla-progress-transition", `width ${transitionDuration}`);
                }
                el.style.setProperty("--youla-progress", `${percent}%`);
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
                end: end
            };
            new IntersectionObserver(([entry], observer) => {
                if (!entry.isIntersecting) {
                    return;
                }
                observer.unobserve(el);
                el._x_progress.revealed = true;
                el.style.setProperty("--youla-progress", `${start}%`);
                if (reducedMotion()) {
                    apply(el._x_progress.end, false);
                    return;
                }
                setTimeout(() => apply(el._x_progress.end, true), 500);
            }).observe(el);
        });
    });
})();