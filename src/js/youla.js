(function() {
    "use strict";
    function register(kind, target, name, callback) {
        if (!target[name]) {
            target[name] = callback;
        } else {
            console.warn(`Youla.js: ${kind} '${name}' already exists.`);
        }
    }
    let directives = {};
    function directive(name, callback) {
        register("directive", directives, `u-${name}`, callback);
    }
    function getDirective(name) {
        return directives[name];
    }
    const compiledCache = new Map;
    function saferEval(expression, dataContext, additionalHelperVariables = {}, noReturn = false) {
        const helperNames = Object.keys(additionalHelperVariables);
        const cacheKey = `${noReturn ? 1 : 0}:${helperNames.join(",")}:${expression}`;
        let fn = compiledCache.get(cacheKey);
        if (!fn) {
            const body = noReturn ? `with($data){${expression}}` : `with($data){return (${expression})}`;
            fn = new Function([ "$data", ...helperNames ], body);
            compiledCache.set(cacheKey, fn);
        }
        return fn(dataContext, ...Object.values(additionalHelperVariables));
    }
    function isNode(value) {
        return !!value && typeof value === "object" && typeof value.nodeType === "number";
    }
    function domReady() {
        return new Promise(resolve => {
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", resolve);
            } else {
                resolve();
            }
        });
    }
    function hasDirective(el, name) {
        return [ ...el.attributes ].some(({name: attrName}) => attrName === name || attrName.startsWith(`${name}.`));
    }
    function closestDirective(el, name) {
        while (el && !hasDirective(el, name)) {
            el = el.parentElement;
        }
        return el;
    }
    function domWalk(el, callback) {
        callback(el);
        const iframeBody = el.tagName === "IFRAME" ? el.contentDocument?.body : null;
        const children = Array.from(iframeBody ? iframeBody.children : el.children);
        for (const node of children) {
            if (hasDirective(node, "u-data")) {
                return;
            }
            if (node.hasAttribute("u-each")) {
                callback(node);
            } else {
                domWalk(node, callback);
            }
        }
    }
    const RESERVED = [ "$el", "$event", "$refs", "$root" ];
    let variables = {};
    function variable(name, callback) {
        const key = `$${name}`;
        if (RESERVED.includes(key)) {
            console.warn(`Youla.js: variable '${key}' is reserved and can't be overridden.`);
            return;
        }
        register("variable", variables, key, callback);
    }
    function resolveVariables(root, el, event) {
        const resolved = {};
        Object.entries(variables).forEach(([name, callback]) => {
            resolved[name] = callback(root, el, event);
        });
        return resolved;
    }
    function getForData(el) {
        let data;
        while (el && !(data = el.__x_for_data)) {
            el = el.parentElement;
        }
        return data;
    }
    function createRefsProxy(root) {
        return new Proxy({}, {
            get(object, property) {
                let ref;
                domWalk(root, el => el.getAttribute("u-ref") === property ? ref = el : null);
                return ref;
            }
        });
    }
    function createMagicVariables(root, el, event) {
        return {
            ...resolveVariables(root, el, event),
            $el: el,
            $event: event,
            $refs: createRefsProxy(root),
            $root: root
        };
    }
    function withMagicVariables(dataContext, magicVariables) {
        return new Proxy(dataContext, {
            get: (target, prop) => prop in magicVariables ? magicVariables[prop] : target[prop],
            has: (target, prop) => prop in magicVariables || prop in target,
            set: (target, prop, value) => prop in magicVariables ? true : Reflect.set(target, prop, value)
        });
    }
    function splitMagicVariables(helperVariables = {}) {
        const magicVariables = {}, otherVariables = {};
        Object.entries(helperVariables).forEach(([key, value]) => {
            (key[0] === "$" ? magicVariables : otherVariables)[key] = value;
        });
        return {
            magicVariables: magicVariables,
            otherVariables: otherVariables
        };
    }
    directive("each", (el, output, attribute, component, additionalHelperVariables = {}) => {
        const {expression: expression} = attribute;
        if (typeof expression !== "string") {
            return;
        }
        let [, item, index = "key", items, join] = expression.match(/^\(?([\w]+)(?:,\s*(\w+))?\)?\s+in\s+(.*?)(?:\s+join\s+'([^']+)')?$/) || [];
        const {magicVariables: magicVariables, otherVariables: otherVariables} = splitMagicVariables(additionalHelperVariables);
        let dataItems;
        if (Number.isInteger(+items)) {
            dataItems = Array.from({
                length: +items
            }, (_, i) => i + 1);
        } else {
            try {
                dataItems = saferEval(`${items}`, withMagicVariables(component.data, magicVariables), otherVariables);
            } catch (error) {
                return;
            }
        }
        if (attribute.modifiers.includes("lazy")) {
            el.setAttribute(attribute.directive, expression);
            el.removeAttribute(attribute.name);
            return;
        }
        while (el.nextSibling) {
            let next = el.nextSibling;
            if (next.nodeType === Node.ELEMENT_NODE && next.hasAttribute("u-each")) {
                break;
            }
            next.remove();
        }
        Object.entries(dataItems ?? []).forEach(([key, dataItem], idx, array) => {
            const clone = el.cloneNode(true);
            clone.removeAttribute("u-each");
            (async () => {
                const numericKey = +key;
                clone.__x_for_data = {
                    ...otherVariables,
                    [item]: dataItem,
                    [index]: Number.isNaN(numericKey) ? key : numericKey
                };
                await component.initialize(clone);
                el.parentNode.appendChild(clone);
                if (array[idx + 1] && join) {
                    clone.insertAdjacentText("afterend", join);
                }
            })();
        });
    });
    directive("html", (el, output, attribute, component) => {
        output = output ?? "";
        if (el._x_html === output) {
            return;
        }
        el._x_html = output;
        el.innerHTML = output;
    });
    function createEvent(eventName, detail = {}) {
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
    const KEY_ALIASES = {
        enter: "Enter",
        esc: "Escape",
        escape: "Escape",
        tab: "Tab",
        space: " ",
        up: "ArrowUp",
        down: "ArrowDown",
        left: "ArrowLeft",
        right: "ArrowRight",
        delete: "Delete",
        backspace: "Backspace"
    };
    const SYSTEM_MODIFIER_KEYS = {
        ctrl: "ctrlKey",
        alt: "altKey",
        shift: "shiftKey",
        meta: "metaKey"
    };
    const BEHAVIOR_MODIFIERS = new Set([ "window", "document", "passive", "capture", "delay", "prevent", "stop", "outside", "once" ]);
    function isKeyModifier(modifier) {
        return !BEHAVIOR_MODIFIERS.has(modifier) && !/^\d+m?s$/.test(modifier);
    }
    function matchesKeyModifiers(e, modifiers) {
        const keyModifiers = modifiers.filter(isKeyModifier);
        return keyModifiers.every(modifier => {
            if (modifier in SYSTEM_MODIFIER_KEYS) {
                return e[SYSTEM_MODIFIER_KEYS[modifier]] === true;
            }
            const expected = KEY_ALIASES[modifier] || modifier;
            return typeof e.key === "string" && e.key.toLowerCase() === expected.toLowerCase();
        });
    }
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
        let missingClasses = classString => classString.split(" ").filter(i => !el.classList.contains(i)).filter(Boolean);
        let addClassesAndReturnUndo = classes => {
            el.classList.add(...classes);
            return () => el.classList.remove(...classes);
        };
        classString = classString === true ? "" : classString || "";
        return addClassesAndReturnUndo(missingClasses(classString));
    }
    function setClassesFromObject(el, classObject) {
        let classes = Object.entries(classObject), split = classString => classString.split(" ").filter(Boolean);
        let forAdd = classes.flatMap(([classString, bool]) => bool ? split(classString) : false).filter(Boolean);
        let forRemove = classes.flatMap(([classString, bool]) => !bool ? split(classString) : false).filter(Boolean);
        const added = forAdd.filter(i => !el.classList.contains(i) && (el.classList.add(i), 
        true));
        const removed = forRemove.filter(i => el.classList.contains(i) && (el.classList.remove(i), 
        true));
        return () => {
            removed.forEach(i => el.classList.add(i));
            added.forEach(i => el.classList.remove(i));
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
        Object.entries(value).forEach(([key, value]) => {
            previousStyles[key] = el.style[key];
            if (!key.startsWith("--")) {
                key = key.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
            }
            el.style.setProperty(key, value);
        });
        setTimeout(() => el.style.length === 0 && el.removeAttribute("style"));
        return () => setStyles(el, previousStyles);
    }
    const ATTRIBUTE_PREFIX = /^(u-|@|:)/;
    const URL_ATTRIBUTES = [ "href", "src", "action", "formaction" ];
    const JAVASCRIPT_URL = /^javascript:/i;
    function isJavascriptUrl(value) {
        return typeof value === "string" && JAVASCRIPT_URL.test(value.replace(/\s+/g, ""));
    }
    function isEventHandlerAttribute(el, name) {
        return /^on\w+$/i.test(name) && name.toLowerCase() in el;
    }
    const DURATION_MODIFIER = /^(\d+)([a-z]+)$/;
    function parseAttribute(name, value) {
        const startsWith = (name.match(ATTRIBUTE_PREFIX) || [ "" ])[0];
        const root = name.replace(startsWith, "");
        const parts = root.split(".");
        const modifiers = root.split(".").slice(1);
        const durationMatch = modifiers.map(m => m.match(DURATION_MODIFIER)).find(Boolean);
        return {
            name: name,
            bind: startsWith === ":",
            directive: startsWith === "u-" ? name.split(".")[0] : "",
            event: startsWith === "@" ? parts[0] : "",
            expression: value,
            modifiers: modifiers,
            duration: durationMatch ? {
                value: Number(durationMatch[1]),
                unit: durationMatch[2]
            } : null,
            literal: typeof value !== "string"
        };
    }
    function getAttributes(el) {
        return [ ...el.attributes ].filter(({name: name}) => ATTRIBUTE_PREFIX.test(name)).map(({name: name, value: value}) => parseAttribute(name, value));
    }
    function updateAttribute(el, name, value) {
        if (isEventHandlerAttribute(el, name)) {
            console.warn(`Youla.js: refusing to bind event-handler attribute "${name}" — use an "@event" listener instead.`);
            return;
        }
        if (URL_ATTRIBUTES.includes(name) && isJavascriptUrl(value)) {
            console.warn(`Youla.js: refusing to bind "${name}" to a "javascript:" URL.`);
            return;
        }
        if (name === "value") {
            if (el.tagName === "SELECT") {
                const selectedValues = [].concat(value).map(v => v + "");
                Array.from(el.options).forEach(option => {
                    option.selected = selectedValues.includes(option.value || option.text);
                });
            } else {
                el.value = value;
            }
        } else if (name === "class") {
            if (el._x_undoAddedClasses) {
                el._x_undoAddedClasses();
            }
            el._x_undoAddedClasses = setClasses(el, value);
        } else if (name === "style") {
            if (el._x_undoAddedStyles) {
                el._x_undoAddedStyles();
            }
            el._x_undoAddedStyles = setStyles(el, value);
        } else if ([ "disabled", "readonly", "required", "checked", "autofocus", "autoplay", "hidden" ].includes(name)) {
            !!value ? el.setAttribute(name, "") : el.removeAttribute(name);
        } else {
            el.setAttribute(name, value);
        }
    }
    const TTL_KEY = "__youla_expires";
    const EXPIRED = Symbol("expired");
    function wrapWithTTL(value, expires) {
        return JSON.stringify({
            [TTL_KEY]: expires.getTime(),
            value: value
        });
    }
    function unwrapTTL(raw) {
        try {
            const parsed = JSON.parse(raw);
            if (parsed && typeof parsed === "object" && TTL_KEY in parsed) {
                return Date.now() > parsed[TTL_KEY] ? EXPIRED : parsed.value;
            }
        } catch (error) {}
        return undefined;
    }
    const storage = {
        get: (name, type = "local") => {
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
                const raw = localStorage.getItem(name);
                if (raw === null) {
                    return;
                }
                const unwrapped = unwrapTTL(raw);
                if (unwrapped === EXPIRED) {
                    localStorage.removeItem(name);
                    return;
                }
                return unwrapped !== undefined ? unwrapped : raw;
            }
        },
        set: (name, value, type = "local", options = {
            path: "/"
        }) => {
            if (!name) return;
            if (value instanceof Object) {
                value = JSON.stringify(value);
            }
            if (type === "cookie") {
                options = Object.assign({
                    samesite: "Lax"
                }, options);
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
                    localStorage.setItem(name, options && options.expires instanceof Date ? wrapWithTTL(value, options.expires) : value);
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
        return [ "cookie", "local" ].some(modifier => modifiers.includes(modifier));
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
    directive("prop", (el, output, attribute, component) => {
        if (el.type === "radio") {
            el.checked = el.value === output;
        } else if (el.type === "checkbox") {
            el.checked = Array.isArray(output) ? output.some(val => val === el.value) : !!output;
        } else {
            updateAttribute(el, "value", output);
        }
        if (isStorageModifier(attribute.modifiers)) {
            const type = getStorageType(attribute.modifiers);
            const expire = getNextModifier(attribute.modifiers, type);
            if (output) {
                storage.set(attribute.expression, output, type, {
                    expires: computeExpires(expire),
                    path: "/",
                    secure: true
                });
            } else {
                storage.set(attribute.expression, null, type, {
                    expires: new Date,
                    path: "/"
                });
            }
        }
    });
    directive("show", (el, output, attribute, component) => {
        if (el._x_originalDisplay === undefined) {
            el._x_originalDisplay = el.style.display === "none" ? "" : el.style.display;
        }
        el.style.display = output ? el._x_originalDisplay : "none";
        el.toggleAttribute("hidden", !output);
    });
    function isPlainObject(value) {
        return typeof value === "object" && value !== null && !Array.isArray(value);
    }
    function isWordChar(char) {
        return char !== undefined && /[\p{L}\p{N}_]/u.test(char);
    }
    function findIsolatedOccurrences(text, needle) {
        const positions = [];
        let from = 0, index;
        while ((index = text.indexOf(needle, from)) !== -1) {
            if (!isWordChar(text[index - 1]) && !isWordChar(text[index + needle.length])) {
                positions.push(index);
            }
            from = index + 1;
        }
        return positions;
    }
    function compilePlaceholders(el, text, data) {
        const tag = el.tagName.toLowerCase();
        const candidates = [];
        for (const [key, value] of Object.entries(data)) {
            if (isPlainObject(value) || Array.isArray(value) || typeof value === "function" || value === undefined) {
                console.warn(`Youla.js: u-text placeholder "${key}" on <${tag}> is a ${typeof value} — only strings, numbers and booleans can be matched. Skipped.`);
                continue;
            }
            const needle = String(value);
            if (needle === "") {
                console.warn(`Youla.js: u-text placeholder "${key}" on <${tag}> is an empty string — nothing to match. Skipped.`);
                continue;
            }
            const positions = findIsolatedOccurrences(text, needle);
            if (positions.length === 0) {
                console.warn(`Youla.js: u-text placeholder "${key}" (value "${needle}") wasn't found in <${tag}>'s text. Skipped.`);
                continue;
            }
            if (positions.length > 1) {
                console.warn(`Youla.js: u-text placeholder "${key}" (value "${needle}") matches ${positions.length} places in <${tag}>'s text — too ambiguous to track. Skipped.`);
                continue;
            }
            candidates.push({
                key: key,
                start: positions[0],
                end: positions[0] + needle.length
            });
        }
        candidates.sort((a, b) => a.start - b.start);
        const accepted = candidates.filter((candidate, i) => {
            const prev = candidates[i - 1];
            const next = candidates[i + 1];
            const collides = prev && prev.end > candidate.start || next && next.start < candidate.end;
            if (collides) {
                console.warn(`Youla.js: u-text placeholder "${candidate.key}" on <${tag}> overlaps another placeholder's match. Skipped.`);
            }
            return !collides;
        });
        const segments = [];
        let cursor = 0;
        accepted.forEach(({key: key, start: start, end: end}) => {
            segments.push(text.slice(cursor, start));
            segments.push({
                key: key
            });
            cursor = end;
        });
        segments.push(text.slice(cursor));
        return segments;
    }
    function renderSegments(segments, data) {
        return segments.map(segment => typeof segment === "string" ? segment : String(data[segment.key] ?? "")).join("");
    }
    directive("text", (el, output, attribute, component) => {
        if (isPlainObject(output)) {
            if (!el._x_text_segments) {
                el._x_text_segments = compilePlaceholders(el, el.textContent, output);
                el._x_text = el.textContent;
                return;
            }
            output = renderSegments(el._x_text_segments, output);
        }
        output = output ?? "";
        if (el._x_text === output) {
            return;
        }
        el._x_text = output;
        el.textContent = output;
    });
    let methods = {};
    function method(name, callback) {
        register("method", methods, `$${name}`, callback);
    }
    function resolveMethods(e, el, component) {
        const resolved = {};
        Object.keys(methods).forEach(key => {
            resolved[key] = methods[key](e, el, component);
        });
        return resolved;
    }
    method("dispatch", (e, el) => (name, detail = {}) => {
        el.dispatchEvent(createEvent(name, detail));
    });
    function debounce(callback, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => callback.apply(this, args), wait);
        };
    }
    function pulsate(callback, wait, immediate = false) {
        immediate && callback();
        return setInterval(callback, wait);
    }
    const RAW = Symbol("raw");
    function toRaw(value) {
        return value && typeof value === "object" && value[RAW] || value;
    }
    const ARRAY_MUTATORS = [ "push", "pop", "shift", "unshift", "splice", "sort", "reverse", "fill", "copyWithin" ];
    function makeObservable(data, onChange) {
        const wrap = target => {
            if (target === null || typeof target !== "object" || isNode(target)) {
                return target;
            }
            target = toRaw(target);
            return new Proxy(target, {
                set: (obj, prop, value) => {
                    value = wrap(value);
                    if (Reflect.set(obj, prop, value)) {
                        onChange(prop);
                    }
                    return true;
                },
                get: (obj, prop) => {
                    if (prop === RAW) {
                        return obj;
                    }
                    if (Array.isArray(obj) && ARRAY_MUTATORS.includes(prop)) {
                        return (...args) => {
                            const result = obj[prop](...args.map(wrap));
                            onChange(prop, true);
                            return result;
                        };
                    }
                    return wrap(obj[prop]);
                }
            });
        };
        return wrap(data);
    }
    function forceRefresh(root) {
        setTimeout(() => {
            const component = root.__x;
            if (component) {
                component.refresh(true);
            }
        }, 0);
    }
    function reactive(data, root) {
        let pending = [];
        return makeObservable(data, prop => {
            if (!pending.includes(prop)) {
                pending.push(prop);
                forceRefresh(root);
                setTimeout(() => pending = [], 0);
            }
        });
    }
    const UNSAFE_KEYS = new Set([ "__proto__", "constructor", "prototype" ]);
    function isUnsafeKey(key) {
        return UNSAFE_KEYS.has(key);
    }
    function setNestedObjectValue(array, lastValue) {
        if (array.length === 0) {
            return lastValue;
        }
        const unsafeKey = array.find(isUnsafeKey);
        if (unsafeKey) {
            console.warn(`Youla.js: refusing to write through unsafe key "${unsafeKey}".`);
            return {};
        }
        let result = {};
        let current = result;
        array.forEach((key, index) => {
            if (index === array.length - 1) {
                current[key] = lastValue;
            } else {
                current[key] = {};
                current = current[key];
            }
        });
        return result;
    }
    function getNestedObjectValue(obj, path) {
        return path.split(".").reduce((acc, key) => isUnsafeKey(key) ? undefined : acc?.[key], obj);
    }
    function hydrateProps(rootElement, data) {
        domWalk(rootElement, el => getAttributes(el).filter(({directive: directive}) => directive === "u-prop").forEach(attribute => {
            let {expression: expression, modifiers: modifiers} = attribute;
            if (![ "input", "select", "textarea" ].includes(el.tagName.toLowerCase())) {
                return;
            }
            if (!el.hasAttribute("name")) {
                el.setAttribute("name", expression.replace(/\.(\w+)/g, "[$1]"));
            }
            let [key, ...prop] = expression.split(".");
            if (isUnsafeKey(key)) {
                console.warn(`Youla.js: u-prop expression "${expression}" uses unsafe key "${key}" — skipped.`);
                return;
            }
            if (data[key] === undefined) {
                let fields = [];
                if (el.type === "checkbox") {
                    fields = closestDirective(el, "u-data").querySelectorAll(`[${CSS.escape(attribute.name)}="${expression.replace(/["\\]/g, "\\$&")}"]`);
                }
                data[key] = setNestedObjectValue(prop, fields.length > 1 ? [] : "");
            }
            let value = generateExpressionForProp(el, data, attribute);
            saferEval(value, withMagicVariables(data, createMagicVariables(rootElement, el)));
            if (isStorageModifier(modifiers)) {
                const type = getStorageType(modifiers);
                const value = storage.get(expression, type);
                if (value) {
                    data[expression] = castToType(data[expression], value);
                }
            }
        }));
        return data;
    }
    function generateExpressionForProp(el, data, attribute) {
        let {expression: expression, modifiers: modifiers} = attribute;
        let rightSideOfExpression, tag = el.tagName.toLowerCase();
        if (el.type === "checkbox") {
            let value = getNestedObjectValue(data, expression);
            if (Array.isArray(value)) {
                rightSideOfExpression = `$el.checked ? ${expression}.concat([$el.value]) : [...${expression}.splice(0, ${expression}.indexOf($el.value)), ...${expression}.splice(${expression}.indexOf($el.value)+1)]`;
            } else {
                rightSideOfExpression = `$el.checked`;
            }
        } else if (el.type === "radio") {
            rightSideOfExpression = `$el.checked ? $el.value : (typeof ${expression} !== 'undefined' ? ${expression} : '')`;
        } else if (tag === "select" && el.multiple) {
            rightSideOfExpression = `Array.from($el.selectedOptions).map(option => ${modifiers.includes("number") ? "parseFloat(option.value || option.text)" : "option.value || option.text"})`;
        } else if (modifiers.includes("number")) {
            return `($el.value = $el.value.replace(/[^\\d]/g, ''), $data.${expression} = $el.value === '' ? '' : parseFloat($el.value))`;
        } else if (modifiers.includes("trim")) {
            rightSideOfExpression = `($el.value = $el.value.replace(/^\\s+|\\s+$/g, ''), $el.value)`;
        } else {
            rightSideOfExpression = "$el.value";
        }
        return `$data.${expression} = ${rightSideOfExpression}`;
    }
    let datas = {};
    function data(name, callback) {
        register("data", datas, name, callback);
    }
    function injectDataProviders(root, obj = {}) {
        Object.entries(datas).forEach(([name, callback]) => {
            obj[name] = callback(root);
        });
        return obj;
    }
    let disconnectObserver;
    const disconnectCleanups = new Map;
    function cleanupOnDisconnect(el, cleanup) {
        if (!disconnectObserver) {
            disconnectObserver = new MutationObserver(() => {
                disconnectCleanups.forEach((cleanups, target) => {
                    if (!target.isConnected) {
                        cleanups.forEach(fn => fn());
                        disconnectCleanups.delete(target);
                    }
                });
            });
            disconnectObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        if (!disconnectCleanups.has(el)) {
            disconnectCleanups.set(el, []);
        }
        disconnectCleanups.get(el).push(cleanup);
    }
    function isArrowFunction(fn) {
        const source = Function.prototype.toString.call(fn);
        if (/^\s*(async\s+)?function/.test(source)) {
            return false;
        }
        const braceIndex = source.indexOf("{");
        const arrowIndex = source.indexOf("=>");
        return arrowIndex !== -1 && (braceIndex === -1 || arrowIndex < braceIndex);
    }
    class Component {
        constructor(el) {
            let dataProviderContext = injectDataProviders(el);
            const {expression: expression, modifiers: modifiers} = getAttributes(el).find(({directive: directive}) => directive === "u-data") || {
                expression: "{}",
                modifiers: []
            };
            const [, dataExpression, alias] = expression.trim().match(/^([\s\S]+?)\s+as\s+([A-Za-z_$][\w$]*)$/) || [];
            const parentEl = closestDirective(el.parentElement, "u-data");
            this.root = el;
            this.parent = parentEl ? parentEl.__x : null;
            this.name = (dataExpression ?? expression).trim();
            this.alias = alias || null;
            this.storageType = isStorageModifier(modifiers) ? getStorageType(modifiers) : null;
            this.storageExpire = this.storageType ? getNextModifier(modifiers, this.storageType) : null;
            this.rawData = saferEval(this.name || "{}", dataProviderContext);
            this.rawData = hydrateProps(el, this.rawData);
            if (this.storageType) {
                const saved = storage.get(`u-data:${this.name}`, this.storageType);
                if (saved) {
                    try {
                        const parsed = typeof saved === "string" ? JSON.parse(saved) : saved;
                        Object.keys(parsed).forEach(key => {
                            if (!isUnsafeKey(key)) {
                                this.rawData[key] = parsed[key];
                            }
                        });
                    } catch (error) {}
                }
            }
            this.data = this.observeData(this.rawData);
            this.initialize(el);
        }
        persist() {
            if (this.storageType) {
                storage.set(`u-data:${this.name}`, this.rawData, this.storageType, {
                    path: "/",
                    secure: true,
                    expires: computeExpires(this.storageExpire)
                });
            }
        }
        get scope() {
            if (!this.parent) {
                return this.data;
            }
            if (!this._scope) {
                const self = this;
                this._scope = new Proxy({}, {
                    has: (_, prop) => prop in self.data || prop in self.parent.scope,
                    get: (_, prop) => {
                        if (prop === RAW) {
                            return toRaw(self.data);
                        }
                        return prop in self.data ? self.data[prop] : self.parent.scope[prop];
                    },
                    set: (_, prop, value) => {
                        if (prop in self.data || !(prop in self.parent.scope)) {
                            self.data[prop] = value;
                        } else {
                            self.parent.scope[prop] = value;
                        }
                        return true;
                    }
                });
            }
            return this._scope;
        }
        evaluate(expressionOrFn, additionalHelperVariables) {
            let deps = [];
            const makeProxy = data => new Proxy(data, {
                get(target, prop) {
                    if (prop === RAW) {
                        return toRaw(target);
                    }
                    deps.push(prop);
                    if (typeof target[prop] === "object" && target[prop] !== null && !isNode(target[prop])) {
                        return makeProxy(target[prop]);
                    }
                    return target[prop];
                }
            });
            const proxiedData = makeProxy(this.scope);
            const {magicVariables: magicVariables, otherVariables: otherVariables} = splitMagicVariables(additionalHelperVariables);
            const trackedHelperVariables = Object.fromEntries(Object.entries(otherVariables).map(([key, value]) => [ key, typeof value === "object" && value !== null && !isNode(value) ? makeProxy(value) : value ]));
            const contextData = withMagicVariables(proxiedData, magicVariables);
            const output = typeof expressionOrFn === "function" ? expressionOrFn.call(contextData) : saferEval(expressionOrFn, contextData, trackedHelperVariables);
            return {
                output: output,
                deps: deps
            };
        }
        resolveAttributes(el) {
            const self = this;
            const additionalHelperVariables = {
                ...getForData(el),
                ...this.getAliasVariables(),
                ...this.getMagicVariables(el)
            };
            return getAttributes(el).flatMap(attribute => {
                if (attribute.directive !== "u-bind") {
                    return [ attribute ];
                }
                let bindings;
                try {
                    ({output: bindings} = self.evaluate(attribute.expression, additionalHelperVariables));
                } catch (error) {
                    return [];
                }
                if (!bindings || typeof bindings !== "object") {
                    return [];
                }
                return Object.entries(bindings).flatMap(([name, value]) => {
                    const isFn = typeof value === "function";
                    const parsed = parseAttribute(name, value);
                    if (isFn && isArrowFunction(value)) {
                        console.warn(`Youla.js: u-bind key "${name}" is an arrow function — arrow functions don't bind "this" to the component's data. Use a regular function or method shorthand instead: "${name}"() { ... }.`);
                    }
                    const isPlainAttribute = !parsed.directive && !parsed.event && !parsed.bind;
                    if (parsed.directive && !getDirective(parsed.directive)) {
                        el.setAttribute(name, isFn ? value.call(self.data) : value);
                        return [];
                    }
                    return [ {
                        ...parsed,
                        expression: value,
                        bind: parsed.bind || isPlainAttribute,
                        literal: !isFn && (isPlainAttribute || typeof value !== "string")
                    } ];
                });
            });
        }
        observeData(data) {
            this.concernedData = [];
            return makeObservable(data, (prop, force) => {
                if (force) {
                    this.refresh(true);
                    this.persist();
                    return;
                }
                if (!this.concernedData.includes(prop)) {
                    this.concernedData.push(prop);
                    this.refresh();
                    this.persist();
                }
            });
        }
        computeOutput(attribute, additionalHelperVariables, {withDeps: withDeps = false} = {}) {
            const {directive: directive, expression: expression, literal: literal} = attribute;
            let output = expression, deps = [];
            if (directive === "u-each") {
                if (withDeps) {
                    [, deps] = expression.split(" in ");
                }
            } else if (!literal) {
                try {
                    ({output: output, deps: deps} = this.evaluate(expression, additionalHelperVariables));
                } catch (error) {
                    output = undefined;
                }
            }
            return {
                output: output,
                deps: deps
            };
        }
        applyAttribute(el, attribute, output, additionalHelperVariables) {
            const {directive: directive, bind: bind, name: name} = attribute;
            if (bind) {
                updateAttribute(el, name.replace(":", ""), output);
            } else {
                getDirective(directive)(el, output, attribute, this, additionalHelperVariables);
            }
        }
        initialize(root) {
            const self = this;
            domWalk(root, el => {
                if (el.__x_initialized) {
                    return;
                }
                el.__x_initialized = true;
                const additionalHelperVariables = {
                    ...getForData(el),
                    ...self.getAliasVariables(),
                    ...self.getMagicVariables(el)
                };
                self.resolveAttributes(el).forEach(attribute => {
                    let {directive: directive, event: event, expression: expression, modifiers: modifiers, bind: bind} = attribute;
                    let propExpression;
                    if (directive === "u-prop") {
                        propExpression = generateExpressionForProp(el, self.data, attribute);
                        event = [ "select-multiple", "select", "checkbox", "radio" ].includes(el.type) || modifiers.includes("lazy") ? "change" : "input";
                    }
                    if (event) {
                        self.attachListener(el, event, directive === "u-prop" ? [] : modifiers, propExpression || expression);
                    }
                    if (bind || getDirective(directive)) {
                        const {output: output} = self.computeOutput(attribute, additionalHelperVariables);
                        self.applyAttribute(el, attribute, output, additionalHelperVariables);
                    }
                });
            });
        }
        refresh(force = false) {
            const self = this;
            this.pendingForceRefresh = this.pendingForceRefresh || force;
            this.scheduleRefresh ??= debounce(() => {
                const force = self.pendingForceRefresh;
                self.pendingForceRefresh = false;
                domWalk(self.root, el => {
                    const additionalHelperVariables = {
                        ...getForData(el),
                        ...self.getAliasVariables(),
                        ...self.getMagicVariables(el)
                    };
                    self.resolveAttributes(el).forEach(attribute => {
                        const {directive: directive, bind: bind, name: name} = attribute;
                        if (bind || getDirective(directive)) {
                            el.__x_deps ??= {};
                            const previousDeps = el.__x_deps[name];
                            if (!force && previousDeps && !previousDeps.some(dep => self.concernedData.includes(dep))) {
                                return;
                            }
                            const {output: output, deps: deps} = self.computeOutput(attribute, additionalHelperVariables, {
                                withDeps: true
                            });
                            el.__x_deps[name] = deps;
                            if (force || !previousDeps || self.concernedData.some(dep => deps.includes(dep))) {
                                self.applyAttribute(el, attribute, output, additionalHelperVariables);
                            }
                        }
                    });
                });
                self.concernedData = [];
            }, 0);
            this.scheduleRefresh();
        }
        attachListener(el, event, modifiers, expression) {
            const wrapHandler = (callback, wrapper) => e => wrapper(callback, e);
            let target = el;
            let options = {};
            let handler = e => this.invokeListener(expression, e, el);
            if (modifiers.includes("window")) {
                target = el.ownerDocument.defaultView;
            }
            if (modifiers.includes("document")) {
                target = el.ownerDocument;
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
                handler = wrapHandler(handler, (next, e) => {
                    e.preventDefault();
                    next(e);
                });
            }
            if (modifiers.includes("stop")) {
                handler = wrapHandler(handler, (next, e) => {
                    e.stopPropagation();
                    next(e);
                });
            }
            if (modifiers.includes("outside")) {
                target = el.ownerDocument;
                handler = wrapHandler(handler, (next, e) => {
                    if (el.contains(e.target)) {
                        return;
                    }
                    if (el.offsetWidth < 1 && el.offsetHeight < 1) {
                        return;
                    }
                    if (e.target.isConnected === false) {
                        return;
                    }
                    next(e);
                });
            }
            if (modifiers.some(isKeyModifier)) {
                handler = wrapHandler(handler, (next, e) => matchesKeyModifiers(e, modifiers) && next(e));
            }
            if (modifiers.includes("once")) {
                options.once = true;
            }
            if (event === "load") {
                handler(createEvent(event, {}));
            }
            if (event === "intersect") {
                const observer = new IntersectionObserver(entries => entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        handler(entry);
                        if (modifiers.includes("once")) {
                            observer.disconnect();
                        }
                    }
                }));
                observer.observe(el);
                cleanupOnDisconnect(el, () => observer.disconnect());
            }
            target.addEventListener(event, handler, options);
            if (target !== el) {
                cleanupOnDisconnect(el, () => target.removeEventListener(event, handler, options));
            }
        }
        invokeListener(expressionOrFn, e, target) {
            const contextData = withMagicVariables(this.scope, this.getMagicVariables(target, e));
            if (typeof expressionOrFn === "function") {
                expressionOrFn.call(contextData, e);
                return;
            }
            const expression = expressionOrFn;
            const methods = resolveMethods(e, target, this);
            const data = getForData(target);
            saferEval(expression, contextData, {
                ...this.getAliasVariables(),
                ...methods,
                ...data
            }, true);
        }
        getAliasVariables() {
            return this.alias ? {
                [this.alias]: this.data
            } : {};
        }
        getMagicVariables(el, event) {
            return createMagicVariables(this.root, el, event);
        }
    }
    const Youla = {
        data: data,
        debounce: debounce,
        directive: directive,
        forceRefresh: forceRefresh,
        method: method,
        pulsate: pulsate,
        reactive: reactive,
        variable: variable,
        start: async function() {
            document.dispatchEvent(createEvent("youla:init"));
            await domReady();
            this.componentDiscover(el => this.componentInitialize(el));
            this.componentWatch(el => this.componentInitialize(el));
        },
        componentDiscover: callback => {
            Array.from(document.querySelectorAll("*")).filter(el => hasDirective(el, "u-data")).forEach(callback);
        },
        componentWatch: callback => {
            let observer = new MutationObserver(mutations => mutations.forEach(mutation => Array.from(mutation.addedNodes).filter(node => node.nodeType === 1 && hasDirective(node, "u-data")).forEach(callback)));
            observer.observe(document.querySelector("body"), {
                childList: true,
                subtree: true
            });
        },
        componentInitialize: el => {
            el.__x = new Component(el);
        }
    };
    window.Youla = Youla;
    window.Youla.start();
})();