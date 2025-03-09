/* X.js MIT license */
(function() {
    "use strict";
    function setClasses(el, value) {
        if (Array.isArray(value)) {
            value = value.join(" ");
        } else if (typeof value === "function") {
            value = value();
        } else if (typeof value === "object" && value !== null) {
            return setClassesFromObject(el, value);
        }
        return setClassesFromString(el, value);
    }
    function setClassesFromString(el, classString) {
        let missingClasses = classString => classString.split(" ").filter((i => !el.classList.contains(i))).filter(Boolean);
        let addClassesAndReturnUndo = classes => {
            el.classList.add(...classes);
            return () => el.classList.remove(...classes);
        };
        classString = classString === true ? "" : classString || "";
        return addClassesAndReturnUndo(missingClasses(classString));
    }
    function setClassesFromObject(el, classObject) {
        let classes = Object.entries(classObject), split = classString => classString.split(" ").filter(Boolean);
        let forAdd = classes.flatMap((([classString, bool]) => bool ? split(classString) : false)).filter(Boolean);
        let forRemove = classes.flatMap((([classString, bool]) => !bool ? split(classString) : false)).filter(Boolean);
        const added = forAdd.filter((i => !el.classList.contains(i) && (el.classList.add(i),
            true)));
        const removed = forRemove.filter((i => el.classList.contains(i) && (el.classList.remove(i),
            true)));
        return () => {
            removed.forEach((i => el.classList.add(i)));
            added.forEach((i => el.classList.remove(i)));
        };
    }
    function setStyles(el, value) {
        if (typeof value === "object" && value !== null) {
            return setStylesFromObject(el, value);
        }
        return ((el, value) => {
            let cache = el.getAttribute("style", value);
            el.setAttribute("style", value);
            return () => {
                el.setAttribute("style", cache || "");
            };
        })(el, value);
    }
    function setStylesFromObject(el, value) {
        let previousStyles = {};
        Object.entries(value).forEach((([key, value]) => {
            previousStyles[key] = el.style[key];
            if (!key.startsWith("--")) {
                key = key.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
            }
            el.style.setProperty(key, value);
        }));
        setTimeout((() => el.style.length === 0 && el.removeAttribute("style")));
        return () => setStyles(el, previousStyles);
    }
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout((() => func.apply(this, args)), wait);
        };
    }
    function pulsate(func, wait, immediate) {
        immediate && func();
        return setInterval(func, wait);
    }
    function saferEval(expression, dataContext, additionalHelperVariables = {}, noReturn = false) {
        expression = noReturn ? `with($data){${expression}}` : isKebabCase(expression) ? `var result;with($data){result=$data['${expression}']};return result` : `var result;with($data){result=${expression}};return result`;
        return new Function([ "$data", ...Object.keys(additionalHelperVariables) ], expression)(dataContext, ...Object.values(additionalHelperVariables));
    }
    function getAttributes(el) {
        const regexp = /^(v-|@|:)/;
        return [ ...el.attributes ].filter((({name: name}) => regexp.test(name))).map((({name: name, value: value}) => {
            const startsWith = name.match(regexp)[0];
            const root = name.replace(startsWith, "");
            const parts = root.split(".");
            return {
                name: name,
                directive: startsWith === "v-" ? name.split(".")[0] : startsWith === ":" ? "v-bind" : "",
                event: startsWith === "@" ? parts[0] : "",
                expression: value,
                modifiers: root.split(".").slice(1)
            };
        }));
    }
    function updateAttribute(el, name, value) {
        if (name === "value") {
            if (el.type === "radio") {
                el.checked = el.value === value;
            } else if (el.type === "checkbox") {
                el.checked = Array.isArray(value) ? value.some((val => val === el.value)) : !!value;
            } else if (el.tagName === "SELECT") {
                updateSelect(el, value);
            } else {
                el.value = value;
            }
        } else if (name === "class") {
            bindClasses(el, value);
        } else if (name === "style") {
            bindStyles(el, value);
        } else if ([ "disabled", "readonly", "required", "checked", "autofocus", "autoplay", "hidden" ].includes(name)) {
            !!value ? el.setAttribute(name, "") : el.removeAttribute(name);
        } else {
            el.setAttribute(name, value);
        }
    }
    function bindClasses(el, value) {
        if (el._x_undoAddedClasses) {
            el._x_undoAddedClasses();
        }
        el._x_undoAddedClasses = setClasses(el, value);
    }
    function bindStyles(el, value) {
        if (el._x_undoAddedStyles) {
            el._x_undoAddedStyles();
        }
        el._x_undoAddedStyles = setStyles(el, value);
    }
    function updateSelect(el, value) {
        const arrayWrappedValue = [].concat(value).map((value => value + ""));
        Array.from(el.options).forEach((option => {
            option.selected = arrayWrappedValue.includes(option.value || option.text);
        }));
    }
    function eventCreate(eventName, detail = {}) {
        return new CustomEvent(eventName, {
            detail: detail,
            bubbles: true,
            composed: true,
            cancelable: true
        });
    }
    function getNextModifier(modifiers, modifierAfter, defaultValue = "") {
        return modifiers[modifiers.indexOf(modifierAfter) + 1] || defaultValue;
    }
    function isInputField(el) {
        return [ "input", "select", "textarea" ].includes(el.tagName.toLowerCase());
    }
    function isKebabCase(str) {
        return /^[a-z][a-z\d]*(-[a-z\d]+)+$/.test(str);
    }
    function isEmpty(variable) {
        return variable === "" || variable === null || Array.isArray(variable) && variable.length === 0 || typeof variable === "object" && Object.keys(variable).length === 0;
    }
    function domReady() {
        return new Promise((resolve => {
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", resolve);
            } else {
                resolve();
            }
        }));
    }
    function domWalk(el, callback) {
        callback(el);
        let node = el.firstElementChild;
        while (node) {
            if (node.hasAttribute("v-data")) {
                return;
            }
            domWalk(node, callback);
            node = node.nextElementSibling;
        }
    }
    function fetchProps(rootElement, data) {
        const fetched = [];
        domWalk(rootElement, (el => getAttributes(el).forEach((attribute => {
            let {name: name, directive: directive, expression: expression, modifiers: modifiers} = attribute;
            if (directive === "v-prop") {
                if (el.type === "checkbox" && data[expression] === undefined) {
                    data[expression] = rootElement.querySelectorAll(`[${CSS.escape(name)}]`).length > 1 ? [] : "";
                }
                if (isInputField(el)) {
                    let modelExpression = generateExpressionForProp(el, data, expression, modifiers);
                    let oldValue = data[expression] !== undefined ? data[expression] : null, newValue = saferEval(modelExpression, data, {
                        $el: el
                    });
                    data[expression] = oldValue && isEmpty(newValue) ? oldValue : newValue;
                }
                fetched.push({
                    el: el,
                    attribute: attribute
                });
            }
        }))));
        document.dispatchEvent(eventCreate("x:fetched", {
            data: data,
            fetched: fetched
        }));
        return data;
    }
    function generateExpressionForProp(el, data, prop, modifiers) {
        let rightSideOfExpression, tag = el.tagName.toLowerCase();
        if (el.type === "checkbox") {
            if (Array.isArray(data[prop])) {
                rightSideOfExpression = `$el.checked ? ${prop}.concat([$el.value]) : [...${prop}.splice(0, ${prop}.indexOf($el.value)), ...${prop}.splice(${prop}.indexOf($el.value)+1)]`;
            } else {
                rightSideOfExpression = `$el.checked`;
            }
        } else if (el.type === "radio") {
            rightSideOfExpression = `$el.checked ? $el.value : (typeof ${prop} !== 'undefined' ? ${prop} : '')`;
        } else if (tag === "select" && el.multiple) {
            rightSideOfExpression = `Array.from($el.selectedOptions).map(option => ${modifiers.includes("number") ? "parseFloat(option.value || option.text)" : "option.value || option.text"})`;
        } else {
            rightSideOfExpression = modifiers.includes("number") ? "parseFloat($el.value)" : modifiers.includes("trim") ? "$el.value.trim()" : "$el.value";
        }
        if (!el.hasAttribute("name")) {
            el.setAttribute("name", prop);
        }
        return `$data['${prop}'] = ${rightSideOfExpression}`;
    }
    let stores = {};
    function getStores() {
        return stores;
    }
    function store(name, value) {
        if (value === undefined) {
            return stores[name];
        }
        stores[name] = value;
        if (typeof value === "object" && value !== null && value.hasOwnProperty("load") && typeof value.init === "function") {
            stores[name].init();
        }
        initInterceptors(stores[name]);
    }
    function initInterceptors(data) {
        let isObject = val => typeof val === "object" && !Array.isArray(val) && val !== null;
        let recurse = (obj, basePath = "") => {
            Object.entries(Object.getOwnPropertyDescriptors(obj)).forEach((([key, {value: value, enumerable: enumerable}]) => {
                if (enumerable === false || value === undefined) return;
                let path = basePath === "" ? key : `${basePath}.${key}`;
                if (typeof value === "object" && value !== null && value._x_interceptor) {
                    obj[key] = value.initialize(data, path, key);
                } else {
                    if (isObject(value) && value !== obj && !(value instanceof Element)) {
                        recurse(value, path);
                    }
                }
            }));
        };
        return recurse(data);
    }
    let datas = {};
    function data(name, callback) {
        datas[name] = callback;
    }
    function injectDataProviders(obj, context) {
        Object.entries(datas).forEach((([name, callback]) => Object.defineProperty(obj, name, {
            get: () => (...args) => callback.call(context, ...args),
            enumerable: false
        })));
        return obj;
    }
    class Component {
        constructor(el) {
            document.dispatchEvent(eventCreate("x:init", {
                x: this
            }));
            let dataProviderContext = {};
            injectDataProviders(dataProviderContext);
            this.root = el;
            this.rawData = saferEval(el.getAttribute("v-data") || "{}", dataProviderContext);
            this.rawData = fetchProps(el, this.rawData);
            this.data = this.wrapDataInObservable(this.rawData);
            this.initialize(el, this.data);
        }
        store(name, value) {
            return store(name, value);
        }
        evaluate(expression, additionalHelperVariables) {
            let affectedDataKeys = [];
            const proxiedData = new Proxy(this.data, {
                get(object, prop) {
                    affectedDataKeys.push(prop);
                    return object[prop];
                }
            });
            const result = saferEval(expression, proxiedData, additionalHelperVariables);
            return {
                output: result,
                deps: affectedDataKeys
            };
        }
        wrapDataInObservable(data) {
            let self = this;
            self.concernedData = [];
            return new Proxy(data, {
                set(obj, property, value) {
                    const setWasSuccessful = Reflect.set(obj, property, value);
                    if (self.concernedData.indexOf(property) === -1) {
                        self.concernedData.push(property);
                    }
                    self.refresh();
                    return setWasSuccessful;
                }
            });
        }
        initialize(root, data, additionalHelperVariables) {
            const self = this;
            domWalk(root, (el => getAttributes(el).forEach((attribute => {
                let {directive: directive, event: event, expression: expression, modifiers: modifiers} = attribute;
                if (event) {
                    self.registerListener(el, event, modifiers, expression);
                }
                if (directive === "v-prop") {
                    let event = [ "select-multiple", "select", "checkbox", "radio" ].includes(el.type) || modifiers.includes("lazy") ? "change" : "input";
                    self.registerListener(el, event, modifiers, generateExpressionForProp(el, data, expression, modifiers));
                    let {output: output} = self.evaluate(expression, additionalHelperVariables);
                    updateAttribute(el, "value", output);
                }
                if (directive in x.directives) {
                    let output = expression;
                    if (directive !== "v-each") {
                        try {
                            ({output: output} = self.evaluate(expression, additionalHelperVariables));
                        } catch (error) {}
                    }
                    x.directives[directive](el, output, attribute, x, self);
                }
            }))));
        }
        refresh() {
            const self = this;
            debounce((() => {
                domWalk(self.root, (el => getAttributes(el).forEach((attribute => {
                    let {directive: directive, expression: expression} = attribute;
                    if (directive === "v-prop") {
                        let {output: output, deps: deps} = self.evaluate(expression);
                        if (self.concernedData.filter((i => deps.includes(i))).length > 0) {
                            updateAttribute(el, "value", output);
                            document.dispatchEvent(eventCreate("x:refreshed", {
                                attribute: attribute,
                                output: output
                            }));
                        }
                    }
                    if (directive in x.directives) {
                        let output = expression, deps = [];
                        if (directive !== "v-each") {
                            try {
                                ({output: output, deps: deps} = self.evaluate(expression));
                            } catch (error) {}
                        } else {
                            [, deps] = expression.split(" in ");
                        }
                        if (self.concernedData.filter((i => deps.includes(i))).length > 0) {
                            x.directives[directive](el, output, attribute, x, self);
                        }
                    }
                }))));
                self.concernedData = [];
            }), 0)();
        }
        registerListener(el, event, modifiers, expression) {
            const wrapHandler = (callback, wrapper) => e => wrapper(callback, e);
            let target = el;
            let options = {};
            let handler = e => this.runListenerHandler(expression, e);
            if (modifiers.includes("window")) {
                target = window;
            }
            if (modifiers.includes("document")) {
                target = document;
            }
            if (modifiers.includes("passive")) {
                options.passive = true;
            }
            if (modifiers.includes("capture")) {
                options.capture = true;
            }
            if (modifiers.includes("delay")) {
                handler = debounce(handler, Number(getNextModifier(modifiers, "delay").split("ms")[0]) || 250);
            }
            if (modifiers.includes("prevent")) {
                handler = wrapHandler(handler, ((next, e) => {
                    e.preventDefault();
                    next(e);
                }));
            }
            if (modifiers.includes("stop")) {
                handler = wrapHandler(handler, ((next, e) => {
                    e.stopPropagation();
                    next(e);
                }));
            }
            if (modifiers.includes("outside")) {
                target = document;
                handler = wrapHandler(handler, ((next, e) => {
                    if (el.contains(e.target)) return;
                    if (el.offsetWidth < 1 && el.offsetHeight < 1) return;
                    if (e.target.isConnected === false) return;
                    next(e);
                }));
            }
            if (modifiers.includes("once")) {
                options.once = true;
            }
            if (event === "load") {
                handler(eventCreate(event, {}));
            }
            if (event === "intersect") {
                const observer = new IntersectionObserver((entries => entries.forEach((entry => {
                    if (entry.isIntersecting) {
                        handler(entry);
                        if (modifiers.includes("once")) {
                            observer.disconnect();
                        }
                    }
                }))));
                observer.observe(el);
            }
            target.addEventListener(event, handler, options);
        }
        runListenerHandler(expression, e) {
            const methods = {};
            Object.keys(x.methods).forEach((key => {
                methods[key] = x.methods[key](e, e.target, this);
            }));
            let data = {}, el = e.target;
            while (el && !(data = el.__x_for_data)) {
                el = el.parentElement;
            }
            saferEval(expression, this.data, {
                ...{
                    $el: e.target,
                    $event: e,
                    $refs: this.getRefsProxy(),
                    $root: this.root
                },
                ...methods,
                ...data
            }, true);
        }
        getRefsProxy() {
            let self = this;
            return new Proxy({}, {
                get(object, property) {
                    let ref;
                    domWalk(self.root, (el => el.getAttribute("v-ref") === property ? ref = el : null));
                    return ref;
                }
            });
        }
    }
    function extend(...args) {
        args.forEach((fn => typeof fn === "function" && fn()));
    }
    const scripts_x = {
        directives: {},
        methods: {},
        start: async function() {
            await domReady();
            this.discoverComponents((el => this.initializeElement(el)));
            this.listenUninitializedComponentsAtRunTime((el => this.initializeElement(el)));
        },
        extend(...args) {
            extend(args);
        },
        discoverComponents: callback => {
            Array.from(document.querySelectorAll("[v-data]")).forEach(callback);
        },
        listenUninitializedComponentsAtRunTime: callback => {
            let observer = new MutationObserver((mutations => mutations.forEach((mutation => Array.from(mutation.addedNodes).filter((node => node.nodeType === 1 && node.matches("[v-data]"))).forEach(callback)))));
            observer.observe(document.querySelector("body"), {
                childList: true,
                attributes: true,
                subtree: true
            });
        },
        initializeElement: el => {
            el.__x = new Component(el);
        }
    };
    const storage = {
        get: (name, type) => {
            if (!name) return;
            if (type === "cookie") {
                let matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([.$?*|{}()\[\]\\\/+^])/g, "\\$1") + "=([^;]*)"));
                if (matches) {
                    let res = decodeURIComponent(matches[1]);
                    try {
                        return JSON.parse(res);
                    } catch (e) {
                        return res;
                    }
                }
            }
            if (type === "local") {
                return localStorage.getItem(name);
            }
        },
        set: (name, value, type, options = {
            path: "/"
        }) => {
            if (!name) return;
            if (value instanceof Object) {
                value = JSON.stringify(value);
            }
            if (type === "cookie") {
                options = options || {};
                if (options.expires instanceof Date) {
                    options.expires = options.expires.toUTCString();
                }
                let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);
                for (let optionKey in options) {
                    updatedCookie += "; " + optionKey;
                    let optionValue = options[optionKey];
                    if (optionValue !== true) {
                        updatedCookie += "=" + optionValue;
                    }
                }
                document.cookie = updatedCookie;
            }
            if (type === "local") {
                if (value) {
                    localStorage.setItem(name, value);
                } else {
                    localStorage.removeItem(name);
                }
            }
        }
    };
    function computeExpires(str) {
        let lastCh = str.charAt(str.length - 1), value = parseInt(str, 10);
        const methods = {
            y: "FullYear",
            m: "Month",
            d: "Date",
            h: "Hours",
            i: "Minutes",
            s: "Seconds"
        };
        if (lastCh in methods) {
            const date = new Date;
            const method = methods[lastCh];
            date[`set${method}`](date[`get${method}`]() + value);
            return date;
        }
        return null;
    }
    function isStorageModifier(modifiers) {
        return [ "cookie", "local" ].some((modifier => modifiers.includes(modifier)));
    }
    function getStorageType(modifiers) {
        return modifiers.includes("cookie") ? "cookie" : "local";
    }
    function castToType(a, value) {
        const type = typeof a;
        switch (type) {
            case "string":
                return String(value);

            case "number":
                return Number.isInteger(a) ? parseInt(value, 10) : parseFloat(value);

            case "boolean":
                return Boolean(value);

            case "object":
                if (a instanceof Date) {
                    return new Date(value);
                } else if (Array.isArray(a)) {
                    return Array.from(value);
                } else {
                    return Object(value);
                }

            case "undefined":
                if (a === null) {
                    return null;
                }
                return value === "true" ? true : value === "false" ? false : value;

            default:
                return value;
        }
    }
    document.addEventListener("x:refreshed", (({detail: detail}) => {
        const {modifiers: modifiers, directive: directive, expression: expression} = detail.attribute;
        if (directive === "v-prop" && isStorageModifier(modifiers)) {
            const type = getStorageType(modifiers);
            const expire = getNextModifier(modifiers, type);
            if (detail.output) {
                storage.set(expression, detail.output, type, {
                    expires: computeExpires(expire),
                    secure: true
                });
            } else {
                storage.set(expression, null, type, {
                    expires: new Date,
                    path: "/"
                });
            }
        }
    }));
    document.addEventListener("x:fetched", (({detail: detail}) => {
        const {data: data, fetched: fetched} = detail;
        fetched.forEach((item => {
            const {attribute: {modifiers: modifiers, directive: directive, expression: expression}} = item;
            if (directive === "v-prop" && isStorageModifier(modifiers)) {
                const type = getStorageType(modifiers);
                const value = storage.get(expression, type);
                data[expression] = castToType(data[expression], value || data[expression]);
            }
        }));
    }));
    const prefix = "v-";
    function directive(name, callback) {
        name = `${prefix}${name}`;
        if (!scripts_x.directives[name]) {
            scripts_x.directives[name] = callback;
        } else {
            console.warn(`X.js: directive '${name}' is already exists.`);
        }
    }
    let contextStack = [];
    directive("each", ((el, expression, attribute, x, component) => {
        if (typeof expression !== "string") {
            return;
        }
        let [, item, index = "key", items, join] = expression.match(/^\(?([\w]+)(?:,\s*(\w+))?\)?\s+in\s+(.*?)(?:\s+join\s+'([^']+)')?$/) || [];
        let dataItems;
        let hasChildEach = el.querySelector("[v-each]");
        if (Number.isInteger(+items)) {
            dataItems = Array.from({
                length: +items
            }, ((_, i) => i + 1));
        } else {
            if (contextStack.length) {
                items = items.replace(/^[^.]+/, `${contextStack[contextStack.length - 1]}`);
            }
            dataItems = saferEval(`${items}`, component.data);
        }
        if (attribute.modifiers.includes("lazy")) {
            el.setAttribute(attribute.directive, expression);
            el.removeAttribute(attribute.name);
            return;
        }
        while (el.nextSibling) {
            let next = el.nextSibling;
            if (next.nodeType === Node.ELEMENT_NODE && next.hasAttribute("v-each")) {
                break;
            }
            next.remove();
        }
        Object.entries(dataItems ?? []).forEach((([key, dataItem], idx, array) => {
            const clone = el.cloneNode(true);
            clone.removeAttribute("v-each");
            (async () => {
                clone.__x_for_data = {
                    [item]: dataItem,
                    [index]: +key || key
                };
                if (hasChildEach) {
                    contextStack.push(`${items}[${key}]`);
                }
                await component.initialize(clone, component.data, clone.__x_for_data);
                if (hasChildEach) {
                    contextStack.pop();
                }
                el.parentNode.appendChild(clone);
                if (array[idx + 1] && join) {
                    clone.insertAdjacentText("afterend", join);
                }
            })();
        }));
    }));
    directive("bind", ((el, expression, {name: name}, x, component) => {
        if (name === ":attributes" && typeof expression === "object") {
            Object.entries(expression).forEach((([key, value]) => updateAttribute(el, key, value)));
        } else {
            updateAttribute(el, name.replace(":", ""), expression);
        }
    }));
    directive("html", ((el, expression, attribute, x, component) => {
        el.innerHTML = expression;
    }));
    directive("text", ((el, expression, attribute, x, component) => {
        el.innerText = expression;
    }));
    directive("show", ((el, expression, attribute, x, component) => {
        el.style.display = expression ? "block" : "none";
    }));
    directive("hide", ((el, expression, attribute, x, component) => {
        el.style.display = expression ? "block" : "none";
    }));
    const methods_prefix = "$";
    function method(name, callback) {
        name = `${methods_prefix}${name}`;
        if (!scripts_x.methods[name]) {
            scripts_x.methods[name] = callback;
        } else {
            console.warn(`X.js: method '${name}' is already exists.`);
        }
    }
    const BYTES_IN_MB = 1048576;
    method("ajax", ((e, el) => (url, options = {}, callback) => {
        let tagName = el.tagName.toLowerCase(), method = tagName === "form" ? "post" : "get", data = tagName === "form" ? new FormData(el) : new FormData, xhr = new XMLHttpRequest;
        switch (tagName) {
            case "form":
                Array.from(el.querySelectorAll("input[type='file']")).forEach((input => {
                    input.files && [ ...input.files ].forEach((file => data.append(input.name, file)));
                }));
                break;

            case "textarea":
            case "select":
            case "input":
                if (el.type === "file" && el.files) {
                    Array.from(el.files).forEach((file => data.append(el.name, file)));
                } else {
                    el.name && data.append(el.name, el.value);
                }
                break;
        }
        el.classList.add("is-load");
        let submits = el.querySelectorAll('[type="submit"]');
        submits.forEach((submit => Object.assign(submit.style, {
            "background-image": "url(\"data:image/svg+xml;charset=UTF-8,%3csvg width='16' height='16' fill='none' xmlns='http://www.w3.org/2000/svg'%3e%3cstyle%3ecircle %7b animation: 4s a infinite linear, 3s o infinite linear;%7d%40keyframes a %7bfrom%7bstroke-dasharray:100 0%7d50%25%7bstroke-dasharray:0 100%7dto%7bstroke-dasharray:100 0%7d%7d%40keyframes o %7bfrom%7bstroke-dashoffset:75%7dto%7bstroke-dashoffset:375%7d%7d%3c/style%3e%3cpath d='M15 8A7 7 0 111 8a7 7 0 0114 0z' stroke='%23fff' stroke-opacity='.2' stroke-width='2'/%3e%3ccircle cx='8' cy='8' r='7' stroke='%23fff' stroke-opacity='.3' stroke-width='2'/%3e%3c/svg%3e\")",
            "background-repeat": "no-repeat",
            "background-position": "center center",
            "background-size": "1.25em",
            "pointer-events": "none",
            color: "transparent",
            transition: "none"
        })));
        return new Promise((resolve => {
            xhr.open(method, url);
            for (const i in options.headers) {
                if (options.headers.hasOwnProperty(i)) {
                    xhr.setRequestHeader(i, options.headers[i]);
                }
            }
            xhr.withCredentials = options.credentials === "include";
            xhr.onloadstart = xhr.upload.onprogress = event => callback?.(onProgress(event, xhr));
            xhr.onloadend = event => resolve((() => callback?.(onProgress(event, xhr))));
            xhr.send(data);
        })).then((response => {
            el.classList.remove("is-load");
            submits.forEach((submit => submit.removeAttribute("style")));
            return response();
        }));
    }));
    function onProgress(event, xhr) {
        const {loaded: loaded = 0, total: total = 0, type: type} = event;
        const {response: response = "", responseText: responseText = "", status: status = "", responseURL: responseURL = ""} = xhr;
        return {
            blob: new Blob([ response ]),
            json: JSON.parse(responseText || "[]"),
            raw: response,
            status: status,
            url: responseURL,
            loaded: convertTo(loaded),
            total: convertTo(total),
            percent: total > 0 ? Math.round(loaded / total * 100) : 0,
            start: type === "loadstart",
            progress: type === "progress",
            end: type === "loadend"
        };
    }
    function convertTo(number) {
        return Math.round(number / BYTES_IN_MB * 100) / 100;
    }
    method("store", getStores);
    method("dispatch", ((e, el) => (name, detail = {}) => {
        el.dispatchEvent(eventCreate(name, detail));
    }));
    directive("sticky", ((el, expression, attribute, x, component) => {
        let style = el.parentElement.currentStyle || window.getComputedStyle(el.parentElement);
        if (style.position !== "relative") {
            return false;
        }
        let rect = el.getBoundingClientRect();
        let diff = rect.height - document.scrollingElement.offsetHeight;
        let paddingTop = parseInt(style.paddingTop) + 42;
        let paddingBottom = parseInt(style.paddingBottom);
        let lastScroll = 0;
        let bottomPoint = 0;
        let value = "top: " + paddingTop + "px";
        function calcPosition() {
            if (diff > 0) {
                let y = document.scrollingElement.scrollTop;
                if (window.scrollY > lastScroll) {
                    if (y > diff) {
                        bottomPoint = diff * -1 - paddingBottom;
                        value = "top: " + bottomPoint + "px";
                    } else {
                        value = "top: " + (y * -1 - paddingBottom) + "px";
                    }
                } else {
                    bottomPoint = bottomPoint + (lastScroll - window.scrollY);
                    if (bottomPoint < paddingTop) {
                        value = "top: " + bottomPoint + "px";
                    }
                }
            }
            el.setAttribute("style", "position: sticky;" + value);
            lastScroll = window.scrollY;
        }
        [ "load", "scroll", "resize" ].forEach((event => window.addEventListener(event, (() => calcPosition()))));
    }));
    directive("autocomplete", ((el, expression, attribute, x, component) => {
        el.setAttribute("readonly", true);
        el.onfocus = () => setTimeout((() => el.removeAttribute("readonly")), 10);
        el.onblur = () => el.setAttribute("readonly", true);
    }));
    directive("highlight", ((el, expression, {modifiers: modifiers}, x, component) => {
        let lang = modifiers[0] || "html", wrapper = document.createElement("code");
        wrapper.classList.add("language-" + lang);
        wrapper.innerHTML = el.innerHTML;
        el.classList.add("line-numbers");
        el.innerHTML = "";
        el.setAttribute("data-lang", lang.toUpperCase());
        el.appendChild(wrapper);
    }));
    directive("collapse", ((el, expression, attribute, x, component) => {
        function slide(el, isDown, duration) {
            if (typeof duration === "undefined") duration = 200;
            if (typeof isDown === "undefined") isDown = false;
            el.style.overflow = "hidden";
            if (isDown) {
                el.style.display = "block";
            }
            let elProperties = [ "height", "paddingTop", "paddingBottom", "marginTop", "marginBottom" ];
            let elStyles = window.getComputedStyle(el);
            let {height: height, paddingTop: paddingTop, paddingBottom: paddingBottom, marginTop: marginTop, marginBottom: marginBottom} = elProperties.reduce(((acc, prop) => (acc[prop] = parseFloat(elStyles[prop]),
                acc)), {});
            let stepHeight = height / duration;
            let stepPaddingTop = paddingTop / duration;
            let stepPaddingBottom = paddingBottom / duration;
            let stepMarginTop = marginTop / duration;
            let stepMarginBottom = marginBottom / duration;
            let start;
            function step(timestamp) {
                if (start === undefined) {
                    start = timestamp;
                }
                let elapsed = timestamp - start;
                el.style.height = `${isDown ? stepHeight * elapsed : height - stepHeight * elapsed}px`;
                el.style.paddingTop = `${isDown ? stepPaddingTop * elapsed : paddingTop - stepPaddingTop * elapsed}px`;
                el.style.paddingBottom = `${isDown ? stepPaddingBottom * elapsed : paddingBottom - stepPaddingBottom * elapsed}px`;
                el.style.marginTop = `${isDown ? stepMarginTop * elapsed : marginTop - stepMarginTop * elapsed}px`;
                el.style.marginBottom = `${isDown ? stepMarginBottom * elapsed : marginBottom - stepMarginBottom * elapsed}px`;
                if (elapsed >= duration) {
                    [ ...elProperties, "overflow" ].forEach((prop => el.style[prop] = ""));
                    if (!isDown) {
                        el.style.display = "none";
                    }
                } else {
                    window.requestAnimationFrame(step);
                }
            }
            window.requestAnimationFrame(step);
        }
        slide(el, expression);
    }));
    directive("anchor", ((el, expression, attribute, x, component) => {
        let hash = window.location.hash.replace("#", ""), anchor = el.innerText.toLowerCase().replaceAll(" ", "-");
        if (hash && hash === anchor) {
            el.scrollIntoView({
                behavior: "smooth"
            });
        }
        el.addEventListener("click", (e => {
            e.preventDefault();
            window.location.hash = anchor;
            el.scrollIntoView({
                behavior: "smooth"
            });
        }), false);
        const observer = new IntersectionObserver((entries => {
            entries.forEach((entry => {
                if (!entry.isIntersecting || entry.intersectionRatio !== 1) {
                    return;
                }
                window.location.hash = anchor;
            }));
        }), {
            threshold: 1
        });
        observer.observe(el);
    }));
    directive("listen", ((el, expression, attribute, x, component) => {
        if (!expression) {
            return false;
        }
        let name = "listen-node";
        function _play(aud, icn) {
            icn.classList.add("playing");
            aud.play();
            aud.setAttribute("data-playing", "true");
            aud.addEventListener("ended", (function() {
                _pause(aud, icn);
                aud.parentNode.style.background = null;
                return false;
            }));
        }
        function _pause(aud, icn) {
            aud.pause();
            aud.setAttribute("data-playing", "false");
            icn.classList.remove("playing");
        }
        let aud, icn;
        let css = document.createElement("style");
        css.type = "text/css";
        css.innerHTML = ".listen-node {display: inline-block; background:rgba(0, 0, 0, 0.05); padding: 1px 8px 2px; border-radius:3px; cursor: pointer;} .listen-node i {font-size: 0.65em; border: 0.5em solid transparent; border-left: 0.75em solid; display: inline-block; margin-right: 2px;margin-bottom: 1px;} .listen-node .playing { border: 0; border-left: 0.75em double; border-right: 0.5em solid transparent; height: 1em;}";
        document.getElementsByTagName("head")[0].appendChild(css);
        aud = document.createElement("audio");
        icn = document.createElement("i");
        aud.src = el.getAttribute("data-src");
        aud.setAttribute("data-playing", "false");
        el.id = name + "-" + i;
        el.insertBefore(icn, el.firstChild);
        el.appendChild(aud);
        document.addEventListener("click", (e => {
            let aud, elm, icn;
            if (e.target.className === name) {
                aud = e.target.children[1];
                elm = e.target;
                icn = e.target.children[0];
            } else if (e.target.parentElement && e.target.parentElement.className === name) {
                aud = e.target.parentElement.children[1];
                elm = e.target.parentElement;
                icn = e.target;
            }
            if (aud && elm && icn) {
                aud.srt = parseInt(elm.getAttribute("data-start")) || 0;
                aud.end = parseInt(elm.getAttribute("data-end")) || aud.duration;
                if (aud && aud.getAttribute("data-playing") === "false") {
                    if (aud.srt > aud.currentTime || aud.end < aud.currentTime) {
                        aud.currentTime = aud.srt;
                    }
                    _play(aud, icn);
                } else {
                    _pause(aud, icn);
                }
                (function loop() {
                    let d = requestAnimationFrame(loop);
                    let percent = (aud.currentTime - aud.srt) * 100 / (aud.end - aud.srt);
                    percent = percent < 100 ? percent : 100;
                    elm.style.background = "linear-gradient(to right, rgba(0, 0, 0, 0.1)" + percent + "%, rgba(0, 0, 0, 0.05)" + percent + "%)";
                    if (aud.end < aud.currentTime) {
                        _pause(aud, icn);
                        cancelAnimationFrame(d);
                    }
                })();
            }
        }));
    }));
    directive("textarea", ((el, expression, attribute, x, component) => {
        if ("TEXTAREA" !== el.tagName.toUpperCase()) {
            return false;
        }
        el.addEventListener("input", (() => {
            let max = parseInt(expression) || 99, rows = parseInt(el.value.split(/\r|\r\n|\n/).length);
            if (rows > max) {
                return false;
            }
            let styles = getComputedStyle(el, null), border = parseInt(styles.getPropertyValue("border-width")) * 4;
            el.style.height = "auto";
            el.style.height = el.scrollHeight + border + 4 + "px";
        }), false);
    }));
    directive("tooltip", ((el, expression, {modifiers: modifiers}, x, component) => {
        let position, trigger;
        if (modifiers) {
            modifiers.forEach((modifier => {
                position = [ "top", "right", "bottom", "left" ].includes(modifier) ? modifier : "top";
                trigger = [ "hover", "click" ].includes(modifier) ? modifier : "hover";
            }));
        }
        if (position && trigger) {
            try {
                new Drooltip({
                    element: el,
                    trigger: trigger,
                    position: position,
                    background: "#fff",
                    color: "var(--grafema-dark)",
                    animation: "bounce",
                    content: content || null,
                    callback: null
                });
            } catch (e) {
                console.warn("You forgot to connect the library Drooltip.js");
            }
        }
    }));
    directive("progress", ((el, expression, {modifiers: modifiers}, x, component) => {
        new IntersectionObserver(((entries, observer) => {
            entries.forEach((entry => {
                if (entry.isIntersecting) {
                    let [value = 100, from = 0, to = 100, duration = "0ms"] = modifiers;
                    let start = parseInt(from) / parseInt(value) * 100;
                    let end = parseInt(to) / parseInt(value) * 100;
                    if (start > end) {
                        [end, start] = [ start, end ];
                    }
                    el.style.setProperty("--grafema-progress", (start < 0 ? 0 : start) + "%");
                    setTimeout((() => {
                        el.style.setProperty("--grafema-transition", " width " + duration);
                        el.style.setProperty("--grafema-progress", (end > 100 ? 100 : end) + "%");
                    }), 500);
                    observer.unobserve(el);
                }
            }));
        })).observe(el);
    }));
    directive("select", ((el, expression, attribute, x, component) => {
        const settings = {
            showSearch: false,
            hideSelected: false,
            closeOnSelect: true
        };
        if (el.hasAttribute("multiple")) {
            settings.hideSelected = true;
            settings.closeOnSelect = false;
        }
        const custom = JSON.parse(expression || "{}");
        if (typeof custom === "object") {
            Object.assign(settings, custom);
        }
        try {
            new SlimSelect({
                settings: settings,
                select: el,
                data: Array.from(el.options).reduce(((acc, option) => {
                    let image = option.getAttribute("data-image"), icon = option.getAttribute("data-icon"), description = option.getAttribute("data-description") || "";
                    let images = image ? `<img src="${image}" alt />` : "", icons = icon ? `<i class="${icon}"></i>` : "", descriptions = description ? `<span class="ss-description">${description}</span>` : "", html = `${images}${icons}<span class="ss-text">${option.text}${descriptions}</span>`;
                    let optionData = {
                        text: option.text,
                        value: option.value,
                        html: html,
                        selected: option.selected,
                        display: true,
                        disabled: false,
                        mandatory: false,
                        placeholder: false,
                        class: "",
                        style: "",
                        data: {}
                    };
                    if (option.parentElement.tagName === "OPTGROUP") {
                        const optgroupLabel = option.parentElement.getAttribute("label");
                        const optgroup = acc.find((item => item.label === optgroupLabel));
                        if (optgroup) {
                            optgroup.options.push(optionData);
                        } else {
                            acc.push({
                                label: optgroupLabel,
                                options: [ optionData ]
                            });
                        }
                    } else {
                        acc.push(optionData);
                    }
                    return acc;
                }), [])
            });
        } catch {
            console.error("The SlimSelect library is not connected");
        }
    }));
    directive("starter", ((el, expression, attribute, x, component) => {}));
    const reactivity_store = {
        data: {},
        effects: []
    };
    function effect(callback, dependencies) {
        const effectData = {
            callback: callback,
            dependencies: new Set(dependencies)
        };
        function updateDependencies(newDependencies) {
            newDependencies.forEach((dep => effectData.dependencies.add(dep)));
        }
        callback();
        reactivity_store.effects.push(effectData);
        return {
            update: updateDependencies
        };
    }
    function reactive(data) {
        const reactiveData = new Proxy(data, {
            set(target, key, value) {
                target[key] = value;
                for (const effectData of reactivity_store.effects) {
                    if (effectData.dependencies.has(key)) {
                        effectData.callback();
                    }
                }
                return true;
            }
        });
        reactivity_store.data = reactiveData;
        return reactiveData;
    }
    extend((() => directive("step", ((el, expression, attribute, x, component) => {
        const wizard = getWizard(el, component);
        const step = wizard.getStep(el);
        const evaluateCheck = () => [ !!expression, {} ];
        if (step) {
            [step.isComplete, step.errors] = evaluateCheck();
            effect((() => {
                console.log("Current Index:", wizard.currentIndex);
                component.refresh();
            }), Object.keys(wizard));
        }
    }))), (() => method("step", ((e, el, component) => getWizard(el, component)))));
    let wizards = new WeakMap;
    let getWizard = (el, {root: root}) => {
        if (!wizards.has(root)) {
            wizards.set(root, reactive({
                steps: [],
                currentIndex: 0,
                progress() {
                    let current = 0;
                    let complete = 0;
                    let total = 0;
                    for (let index = 0; index < this.steps.length; index++) {
                        const step = this.steps[index];
                        total++;
                        if (index <= this.currentIndex) {
                            current++;
                        }
                        if (index <= this.currentIndex && step.isComplete) {
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
                current() {
                    return this.steps[this.currentIndex] || {
                        el: null,
                        title: null
                    };
                },
                previous() {
                    return this.steps[this.previousIndex()] || {
                        el: null,
                        title: null
                    };
                },
                next() {
                    return this.steps[this.nextIndex()] || {
                        el: null,
                        title: null
                    };
                },
                previousIndex() {
                    return findNextIndex(this.steps, this.currentIndex, -1);
                },
                nextIndex() {
                    return findNextIndex(this.steps, this.currentIndex, 1);
                },
                isStep(index) {
                    if (!Array.isArray(index)) {
                        index = [ index ];
                    }
                    return index.includes(this.currentIndex);
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
                goNext() {
                    this.goto(this.nextIndex());
                },
                canGoNext() {
                    return this.current().isComplete && this.nextIndex() !== null;
                },
                cannotGoNext() {
                    return !this.canGoNext();
                },
                goBack() {
                    this.goto(this.previousIndex());
                },
                canGoBack() {
                    return this.previousIndex() !== null;
                },
                cannotGoBack() {
                    return !this.canGoBack();
                },
                goto(index) {
                    if (index !== null && this.steps[index] !== void 0) {
                        this.currentIndex = index;
                        let action = this.steps[index].action || "";
                        if (action) {
                            this.steps[index].evaluate(action);
                        }
                    }
                    return this.current();
                },
                getStep(el) {
                    let step = this.steps.find((step => step.el === el));
                    if (!step) {
                        el.setAttribute("v-show", "console.log($step.current());$step.current().el === $el");
                        step = {
                            el: el,
                            title: "",
                            isComplete: true,
                            errors: {}
                        };
                        this.steps.push(step);
                    }
                    return step;
                }
            }));
        }
        return wizards.get(root);
    };
    let findNextIndex = (steps, current, direction = 1) => {
        for (let index = current + direction; index >= 0 && index < steps.length; index += direction) {
            if (steps[index]) {
                return index;
            }
        }
        return null;
    };
    method("pickadate", ((e, el) => options => {
        try {
            options = Object.assign({}, {
                inline: true,
                multiple: false,
                ranged: true,
                time: true,
                lang: "ru",
                months: 2,
                timeAmPm: false,
                within: false,
                without: false,
                yearRange: 5,
                weekStart: 1
            }, options);
            new Datepicker(el, options);
        } catch (e) {
            console.error('X.js: "Datepicker" is not defined. Details: https:://github.com/text-mask/text-mask');
        }
    }));
    let seconds = 0, isCountingDown = false;
    method("countdown", (() => ({
        start: (initialSeconds, processCallback, endCallback) => {
            if (isCountingDown) {
                return;
            }
            seconds = initialSeconds;
            isCountingDown = true;
            function countdown() {
                processCallback && processCallback(true);
                if (seconds === 0) {
                    endCallback && endCallback(true);
                    isCountingDown = false;
                } else {
                    seconds--;
                    setTimeout(countdown, 1e3);
                }
            }
            countdown();
        },
        second: seconds
    })));
    let stream = null;
    method("stream", (() => ({
        check(refs) {
            let canvas = refs.canvas, video = refs.video, image = refs.image;
            if (!canvas) {
                console.error("Canvas element is undefined");
                return false;
            }
            if (!video) {
                console.error("Video for selfie preview is undefined");
                return false;
            }
            if (!image) {
                console.error("Image for output selfie is undefined");
                return false;
            }
        },
        isVisible(element) {
            const styles = window.getComputedStyle(element);
            if (styles) {
                return !(styles.visibility === "hidden" || styles.display === "none" || parseFloat(styles.opacity) === 0);
            }
            return false;
        },
        start(refs) {
            let video = refs.video;
            const observer = new MutationObserver((mutations => {
                for (let mutation of mutations) {
                    if (mutation.target === document.body && !stream) {
                        setTimeout((async () => {
                            if (this.isVisible(video)) {
                                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                                    video.srcObject = stream = await navigator.mediaDevices.getUserMedia({
                                        video: true
                                    });
                                } else {
                                    console.error("The browser does not support the getUserMedia API");
                                }
                            }
                        }), 500);
                    }
                }
            }));
            observer.observe(document, {
                childList: true,
                subtree: true,
                attributes: true
            });
        },
        snapshot(refs) {
            this.check(refs);
            this.start(refs);
            let canvas = refs.canvas, video = refs.video, image = refs.image;
            let width = video.offsetWidth, height = video.offsetHeight;
            let imageStyles = window.getComputedStyle(image), imageWidth = parseInt(imageStyles.width, 10), imageHeight = parseInt(imageStyles.height, 10);
            canvas.width = imageWidth;
            canvas.height = imageHeight;
            let offsetTop = (height - imageHeight) / 2, offsetLeft = (width - imageWidth) / 2;
            let ctx = canvas.getContext("2d");
            ctx.imageSmoothingQuality = "low";
            ctx.drawImage(video, offsetLeft * 1.5, offsetTop * 1.5, height * 1.5, height * 1.5, 0, 0, imageWidth, imageHeight);
            let imageData = canvas.toDataURL("image/png");
            if (imageData) {
                image.src = imageData;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            return imageData;
        },
        stop() {
            if (stream) {
                stream.getTracks().forEach((track => track.stop()));
            }
            stream = null;
        }
    })));
    method("password", (() => ({
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
        switch(value) {
            return !!!value;
        },
        check(value) {
            let matchCount = 0;
            let totalCount = 0;
            for (const charset in this.charsets) {
                let requiredCount = this.min[charset], charsetRegex = new RegExp(`[${this.charsets[charset]}]`, "g"), charsetCount = (value.match(charsetRegex) || []).length;
                matchCount += Math.min(charsetCount, requiredCount);
                totalCount += requiredCount;
                this.valid[charset] = charsetCount >= requiredCount;
            }
            if (value.length >= this.min.length) {
                matchCount += 1;
                totalCount += 1;
                this.valid.length = value.length >= this.min.length;
            }
            return Object.assign({
                progress: totalCount === 0 ? totalCount : matchCount / totalCount * 100
            }, this.valid);
        },
        generate() {
            let password = "", types = Object.keys(this.charsets);
            types.forEach((type => {
                let count = Math.max(this.min[type], 0), charset = this.charsets[type];
                for (let i = 0; i < count; i++) {
                    let randomIndex = Math.floor(Math.random() * charset.length);
                    password += charset[randomIndex];
                }
            }));
            while (password.length < this.min.length) {
                let randomIndex = Math.floor(Math.random() * types.length), charType = types[randomIndex], charset = this.charsets[charType], randomCharIndex = Math.floor(Math.random() * charset.length);
                password += charset[randomCharIndex];
            }
            this.check(password);
            return this.shuffle(password);
        },
        shuffle(password) {
            let array = password.split("");
            let currentIndex = array.length;
            let temporaryValue, randomIndex;
            while (currentIndex !== 0) {
                randomIndex = Math.floor(Math.random() * currentIndex);
                currentIndex -= 1;
                temporaryValue = array[currentIndex];
                array[currentIndex] = array[randomIndex];
                array[randomIndex] = temporaryValue;
            }
            return array.join("");
        }
    })));
    method("mask", ((e, el) => mask => {
        if (typeof mask === "undefined") {
            let type = el.getAttribute("type");
            if (type) {
                let exp = "";
                switch (type) {
                    case "tel":
                        exp = /[^ \-()+\d]/g;
                        break;

                    case "number":
                        exp = /[^.-\d]/g;
                        break;

                    case "color":
                        exp = /[^ a-zA-Z(),\d]/g;
                        break;

                    case "domain":
                        break;
                }
                if (exp) {
                    el.value = el.value.replace(exp, "");
                }
            }
        } else if (mask === Object(mask)) {
            el.value = el.value.replace(mask, "");
        } else {
            try {
                function limit(position, symbol, max) {
                    let pos = position;
                    max = max.toString();
                    if (mask.charAt(--pos) === symbol) {
                        if (el.value.charAt(pos) === max.charAt(0)) {
                            return new RegExp("[0-" + max.charAt(1) + "]");
                        } else {
                            return /\d/;
                        }
                    }
                    return new RegExp("[0-" + max.charAt(0) + "]");
                }
                let maskArr = mask.match(/(\{[^}]+?\})|(.)/g), position = -1;
                maskArr = maskArr.map((symbol => {
                    ++position;
                    switch (symbol) {
                        case "i":
                            return limit(position, symbol, 59);

                        case "H":
                            return limit(position, symbol, 23);

                        case "D":
                            return limit(position, symbol, 31);

                        case "M":
                            return limit(position, symbol, 12);

                        case "Y":
                        case "0":
                            return /\d/;

                        default:
                            if (/\{[^}]+?\}/.test(symbol)) {
                                return new RegExp(symbol.slice(2, -2));
                            }
                            return symbol;
                    }
                }));
                vanillaTextMask.maskInput({
                    inputElement: el,
                    guide: false,
                    mask: maskArr
                });
            } catch (e) {
                console.error('X.js: "vanillaTextMask" is not defined. Details: https:://github.com/text-mask/text-mask');
            }
        }
    }));
    method("modal", ((e, el) => ({
        open: (id, animation) => {
            setTimeout((() => {
                let modal = document.getElementById(id);
                if (modal) {
                    modal.classList.add("is-active", animation || "fade");
                }
                document.body.style.overflow = "hidden";
            }), 25);
        },
        close: animation => {
            let modal = el.closest(".modal");
            if (modal !== null && modal.classList.contains("is-active")) {
                modal.classList.remove("is-active", animation || "fade");
                document.body.style.overflow = "";
            }
        }
    })));
    method("notice", ((e, el) => ({
        items: [],
        add(message) {
            this.items.push({
                id: e.timeStamp,
                type: e.detail.type,
                message: message
            });
        },
        remove(notification) {
            console.log(notification);
            console.log(this.items);
            this.items = this.items.filter((i => i.id !== notification.id));
        }
    })));
    store("notice", {
        items: {},
        duration: 4e3,
        info(message) {
            this.notify(message, "info");
        },
        success(message) {
            this.notify(message, "success");
        },
        warning(message) {
            this.notify(message, "warning");
        },
        error(message) {
            this.notify(message, "error");
        },
        loading(message) {
            this.notify(message, "loading");
        },
        close(id) {
            if (typeof this.items[id] !== "undefined") {
                this.items[id].selectors.push("hide");
                setTimeout((() => delete this.items[id]), 1e3);
            }
        },
        add(message, type) {
            if (message) {
                let animationName = Math.random().toString(36).replace(/[^a-z]+/g, "").substr(0, 5), timestamp = Date.now();
                this.items[timestamp] = {
                    anim: `url("data:image/svg+xml;charset=UTF-8,%3csvg width='24' height='24' fill='none' xmlns='http://www.w3.org/2000/svg'%3e%3cstyle%3ecircle %7b animation: ${this.duration}ms ${animationName} linear;%7d%40keyframes ${animationName} %7bfrom%7bstroke-dasharray:0 70%7dto%7bstroke-dasharray:70 0%7d%7d%3c/style%3e%3ccircle cx='12' cy='12' r='11' stroke='%23000' stroke-opacity='.2' stroke-width='2'/%3e%3c/svg%3e")`,
                    message: message,
                    closable: true,
                    selectors: [ type || "info" ],
                    classes() {
                        return this.selectors.map((x => "notice__item--" + x)).join(" ");
                    }
                };
                setTimeout((() => this.close(timestamp)), this.duration);
            }
        }
    });
    data("timer", ((endDate, startDate) => ({
        timer: null,
        end: endDate,
        day: "01",
        hour: "01",
        min: "01",
        sec: "01",
        init() {
            let start = startDate || (new Date).valueOf(), end = new Date(this.end).valueOf();
            if (start < end) {
                let diff = Math.round((end - start) / 1e3);
                let t = this;
                this.timer = pulsate((() => {
                    t.day = ("0" + parseInt(diff / (60 * 60 * 24), 10)).slice(-2);
                    t.hour = ("0" + parseInt(diff / (60 * 60) % 24, 10)).slice(-2);
                    t.min = ("0" + parseInt(diff / 60 % 60, 10)).slice(-2);
                    t.sec = ("0" + parseInt(diff % 60, 10)).slice(-2);
                    if (--diff < 0) {
                        t.days = t.hour = t.min = t.sec = "00";
                    }
                }), 1e3, true);
            }
        }
    })));
    data("dropdown", (() => ({
        open: false,
        toggle() {
            console.log(this);
            this.open = !this.open;
        }
    })));
    data("avatar", (() => ({
        content: "",
        image: "",
        add(event, callback) {
            let file = event.target.files[0];
            if (file) {
                let reader = new FileReader;
                reader.onload = e => {
                    this.image = e.target.result;
                };
                reader.readAsDataURL(file);
            }
            if (callback) {
                callback();
            }
        },
        remove() {
            let root = this.$el.closest("[v-data]"), input = root && root.querySelector('input[type="file"]');
            if (input) {
                input.value = "";
            }
            this.image = "";
        },
        getInitials(string, letters = 2) {
            const wordArray = string.split(" ").slice(0, letters);
            if (wordArray.length >= 2) {
                return wordArray.reduce(((accumulator, currentValue) => `${accumulator}${currentValue[0].charAt(0)}`.toUpperCase()), "");
            }
            return wordArray[0].charAt(0).toUpperCase();
        }
    })));
    data("builder", (() => ({
        default: {
            location: "post",
            operator: "===",
            value: "editor"
        },
        groups: [ {
            rules: [ {
                location: "post_status",
                operator: "!=",
                value: "contributor"
            } ]
        } ],
        addGroup() {
            let pattern = JSON.parse(JSON.stringify(this.default));
            this.groups.push({
                rules: [ pattern ]
            });
        },
        removeGroup(index) {
            this.groups.splice(index, 1);
        },
        addRule(key) {
            let pattern = JSON.parse(JSON.stringify(this.default));
            this.groups[key].rules.push(pattern);
        },
        removeRule(key, index) {
            this.groups[key].rules.splice(index, 1);
        },
        submit() {
            let groups = JSON.parse(JSON.stringify(this.groups));
            console.log(groups);
        }
    })));
    data("table", (() => ({
        init() {
            document.addEventListener("keydown", (e => {
                let key = window.event ? event : e;
                if (!!key.shiftKey) {
                    this.selection.shift = true;
                }
            }));
            document.addEventListener("keyup", (e => {
                let key = window.event ? event : e;
                if (!key.shiftKey) {
                    this.selection.shift = false;
                }
            }));
        },
        selection: {
            box: {},
            shift: false,
            addMore: true
        },
        items: [],
        trigger: {
            ["@change"](e) {
                let inputs = document.querySelectorAll('input[name="item[]"]');
                if (inputs.length) {
                    inputs.forEach((input => input.checked = e.target.checked));
                }
            }
        },
        switcher: {
            ["@click"](e) {
                let checkboxes = document.querySelectorAll('input[name="item[]"]');
                let nodeList = Array.prototype.slice.call(document.getElementsByClassName("cb"));
                this.selection.addMore = !!e.target.checked;
                if (this.selection.shift) {
                    this.selection.box[1] = nodeList.indexOf(e.target.parentNode);
                    let i = this.selection.box[0], x = this.selection.box[1];
                    if (i > x) {
                        for (;x < i; x++) {
                            checkboxes[x].checked = this.selection.addMore;
                        }
                    }
                    if (i < x) {
                        for (;i < x; i++) {
                            checkboxes[i].checked = this.selection.addMore;
                        }
                    }
                    this.selection.box[0] = undefined;
                    this.selection.box[1] = undefined;
                } else {
                    this.selection.box[0] = nodeList.indexOf(e.target.parentNode);
                }
            }
        }
    })));
    window.x = scripts_x;
    window.x.start();
})();