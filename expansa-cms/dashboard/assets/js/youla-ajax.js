(function() {
    document.addEventListener('youla:init', () => {
        const BYTES_IN_MB = 1048576;
        const METHODS = [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ];
        const SAFE = [ 'GET', 'HEAD' ];
        const GLOBAL_ACTIONS = [ 'changeURL', 'redirect', 'reload', 'notify' ];
        const DEFAULTS = {
            baseURL: '',
            credentials: true,
            timeout: 0,
            headers: {},
            csrf: {
                cookie: 'XSRF-TOKEN',
                header: 'X-XSRF-TOKEN'
            },
            errors: {
                field: '[name="{name}"]',
                wrapper: null,
                anchor: null,
                messageClass: 'ajax-error',
                invalidClass: 'is-invalid'
            },
            messages: {
                failed: 'Something went wrong. Please try again later.',
                network: 'No connection. Check the internet and try again.'
            }
        };
        Youla.ajax ??= {};
        let errorId = 0;
        Youla.method('ajax', (e, el) => {
            const ajax = (method, route, payload, onProgress, options = {}) => {
                const config = settings();
                abortPrevious(el);
                clearErrors(scopeOf(el));
                const xhr = el.__ajax = new XMLHttpRequest;
                const body = buildRequestBody(el, payload);
                const safe = SAFE.includes(method);
                const url = resolveURL(route, config.baseURL, safe ? body : null);
                const done = toggleLoading(el);
                xhr.open(method, url);
                xhr.withCredentials = options.credentials ?? config.credentials;
                xhr.timeout = options.timeout ?? config.timeout;
                const headers = {
                    ...config.headers,
                    ...options.headers
                };
                if (config.csrf && !safe && isSameOrigin(url) && !hasHeader(headers, config.csrf.header)) {
                    const token = readCookie(config.csrf.cookie);
                    if (token) {
                        headers[config.csrf.header] = token;
                    }
                }
                Object.entries(headers).forEach(([name, value]) => xhr.setRequestHeader(name, value));
                if (onProgress) {
                    xhr.onloadstart = xhr.upload.onprogress = event => onProgress(readProgress(event, xhr));
                }
                xhr.onloadend = event => {
                    onProgress?.(readProgress(event, xhr));
                    done();
                };
                return new Promise((resolve, reject) => {
                    xhr.__reject = reject;
                    xhr.onerror = xhr.ontimeout = () => {
                        notify(config.messages.network, 'error');
                        reject(handled(new Error('Youla.js: "$ajax" network error.')));
                    };
                    xhr.onload = () => {
                        const parsed = parseJSON(xhr.responseText);
                        if (xhr.status < 200 || xhr.status >= 300) {
                            showErrors(el, parsed, config);
                            reject(handled(Object.assign(new Error(`Youla.js: "$ajax" failed with status ${xhr.status}.`), {
                                status: xhr.status,
                                data: parsed ?? xhr.responseText
                            })));
                            return;
                        }
                        const data = parsed?.data ?? parsed ?? xhr.responseText;
                        let settled = false;
                        const override = value => {
                            settled = true;
                            resolve(value);
                        };
                        try {
                            document.dispatchEvent(new CustomEvent(`ajax:${route}`, {
                                detail: {
                                    data,
                                    el,
                                    resolve: override
                                },
                                bubbles: true,
                                composed: true,
                                cancelable: true
                            }));
                            if (Array.isArray(data)) {
                                data.forEach(item => applyFragment(item, config));
                            }
                        } catch (error) {
                            console.error('Youla.js: "$ajax" fragment handling failed.', error);
                        }
                        if (!settled) {
                            resolve(data);
                        }
                    };
                    xhr.send(safe ? null : body);
                });
            };
            return METHODS.reduce((methods, method) => ({
                ...methods,
                [method.toLowerCase()]: (route, payload, onProgress, options) => ajax(method, route, payload, onProgress, options)
            }), {});
        });
        window.addEventListener('unhandledrejection', event => {
            if (event.reason?.handled) {
                event.preventDefault();
            }
        });
        function settings() {
            const custom = Youla.ajax || {};
            const merge = key => custom[key] === false ? false : {
                ...DEFAULTS[key],
                ...custom[key]
            };
            return {
                ...DEFAULTS,
                ...custom,
                csrf: merge('csrf'),
                errors: merge('errors'),
                messages: merge('messages')
            };
        }
        function notify(message, type, duration) {
            document.querySelector('[u-data="notice"]')?.__x?.data?.add(message, type, duration);
        }
        function handled(error) {
            error.handled = true;
            return error;
        }
        function resolveURL(route, baseURL, query) {
            const url = /^https?:\/\//.test(route) ? route : baseURL + route;
            if (!query) {
                return url;
            }
            const params = new URLSearchParams;
            query.forEach((value, key) => typeof value === 'string' && params.append(key, value));
            const search = params.toString();
            if (!search) {
                return url;
            }
            const [path, hash = ''] = url.split('#');
            return path + (path.includes('?') ? '&' : '?') + search + (hash && `#${hash}`);
        }
        function scopeOf(el) {
            return el.closest('form') ?? el.closest('[u-data]') ?? document;
        }
        function showErrors(el, data, config) {
            const scope = scopeOf(el);
            const errors = data?.errors && typeof data.errors === 'object' ? data.errors : {};
            const unplaced = [];
            let first = null;
            Object.entries(errors).forEach(([name, messages]) => {
                const texts = [].concat(messages).filter(text => typeof text === 'string' && text !== '');
                const input = config.errors && !/^\d+$/.test(name) ? findField(scope, name, config.errors) : null;
                if (!input) {
                    unplaced.push(...texts);
                    return;
                }
                markField(input, texts, config.errors);
                first ??= input;
            });
            if (!first && !unplaced.length) {
                unplaced.push(typeof data?.message === 'string' && data.message ? data.message : config.messages.failed);
            }
            [ ...new Set(unplaced) ].forEach(text => notify(text, 'error'));
            first?.focus({
                preventScroll: true
            });
            first?.scrollIntoView({
                block: 'center',
                behavior: 'smooth'
            });
        }
        function findField(scope, name, options) {
            const selector = options.field.replaceAll('{name}', CSS.escape(name));
            return [ ...scope.querySelectorAll(selector) ].find(input => {
                const box = options.wrapper && input.closest(options.wrapper) || input;
                return input.type !== 'hidden' && (box.checkVisibility?.() ?? true);
            }) ?? null;
        }
        function markField(input, texts, options) {
            const wrapper = options.wrapper && input.closest(options.wrapper) || input;
            const anchor = options.anchor && input.closest(options.anchor) || input;
            const error = document.createElement('div');
            error.className = options.messageClass;
            error.id = `ajax-error-${++errorId}`;
            error.textContent = texts.join(' ');
            anchor.after(error);
            wrapper.classList.add(options.invalidClass);
            input.setAttribute('aria-invalid', 'true');
            input.setAttribute('aria-errormessage', error.id);
            const clear = input.__ajaxClear = () => {
                error.remove();
                wrapper.classList.remove(options.invalidClass);
                input.removeAttribute('aria-invalid');
                input.removeAttribute('aria-errormessage');
                input.removeEventListener('input', clear);
                input.removeEventListener('change', clear);
                delete input.__ajaxClear;
            };
            input.addEventListener('input', clear);
            input.addEventListener('change', clear);
        }
        function clearErrors(scope) {
            scope.querySelectorAll('[aria-errormessage]').forEach(input => input.__ajaxClear?.());
        }
        function hasHeader(headers, name) {
            return Object.keys(headers).some(key => key.toLowerCase() === name.toLowerCase());
        }
        function readCookie(name) {
            const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const match = document.cookie.match(`(?:^|; )${escaped}=([^;]*)`);
            return match ? decodeURIComponent(match[1]) : null;
        }
        function isSameOrigin(url) {
            return new URL(url, window.location.href).origin === window.location.origin;
        }
        function abortPrevious(el) {
            const xhr = el.__ajax;
            if (!xhr) {
                return;
            }
            xhr.onload = xhr.onerror = xhr.ontimeout = xhr.onloadstart = xhr.onloadend = xhr.upload.onprogress = null;
            xhr.abort();
            xhr.__reject?.(handled(new DOMException('Superseded by a new "$ajax" call on the same element.', 'AbortError')));
        }
        function toggleLoading(el) {
            const elements = [ el, ...el.querySelectorAll('[type="submit"]') ];
            elements.forEach(element => element.classList.add('is-load'));
            return () => elements.forEach(element => element.classList.remove('is-load'));
        }
        function buildRequestBody(el, payload) {
            const isForm = el.tagName === 'FORM';
            const formData = isForm ? new FormData(el) : new FormData;
            if (!isForm && el.name && 'value' in el) {
                if (el.type === 'file') {
                    Array.from(el.files || []).forEach(file => formData.append(el.name, file));
                } else {
                    formData.append(el.name, el.value);
                }
            }
            if (payload && typeof payload === 'object') {
                Object.entries(payload).forEach(([key, value]) => value != null && formData.append(key, value));
            }
            return formData;
        }
        function readProgress(event, xhr) {
            const {loaded = 0, total = 0, type} = event;
            const {responseText: raw = '', status = 0, responseURL: url = ''} = xhr;
            return {
                raw,
                get json() {
                    return parseJSON(raw);
                },
                get blob() {
                    return new Blob([ raw ]);
                },
                status,
                url,
                loaded: toMegabytes(loaded),
                total: toMegabytes(total),
                percent: total > 0 ? Math.round(loaded / total * 100) : 0,
                start: type === 'loadstart',
                progress: type === 'progress',
                end: type === 'loadend'
            };
        }
        function parseJSON(text) {
            try {
                return text ? JSON.parse(text) : null;
            } catch {
                return null;
            }
        }
        function toMegabytes(bytes) {
            return Math.round(bytes / BYTES_IN_MB * 100) / 100;
        }
        function applyFragment(item, config) {
            if (!item || typeof item !== 'object') {
                return;
            }
            const {target, ...actions} = item;
            const targets = target ? document.querySelectorAll(target) : [ null ];
            targets.forEach(element => {
                Object.entries(actions).forEach(([key, value]) => {
                    const [action, delay] = key.split(':');
                    if (!element && !GLOBAL_ACTIONS.includes(action)) {
                        return;
                    }
                    setTimeout(() => {
                        try {
                            runFragmentAction(action, element, value, config);
                        } catch (error) {
                            console.error(`Youla.js: "$ajax" fragment action "${action}" failed.`, error);
                        }
                    }, Number(delay) || 0);
                });
            });
        }
        function runFragmentAction(action, target, value, config) {
            switch (action) {
              case 'changeURL':
                window.history.pushState(null, '', value || '');
                break;

              case 'redirect':
                window.location = value || '';
                break;

              case 'reload':
                window.location.reload();
                break;

              case 'scrollTo':
                window.scrollBy({
                    top: target.getBoundingClientRect().top,
                    behavior: 'smooth'
                });
                break;

              case 'scrollIntoView':
                target.scrollIntoView(value);
                break;

              case 'value':
                target.value = value ?? '';
                target.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                break;

              case 'update':
                target.innerHTML = value || '';
                break;

              case 'replace':
                target.outerHTML = value || '';
                break;

              case 'remove':
                target.remove();
                break;

              case 'before':
              case 'prepend':
              case 'append':
              case 'after':
                target.insertAdjacentHTML({
                    before: 'beforebegin',
                    prepend: 'afterbegin',
                    append: 'beforeend',
                    after: 'afterend'
                }[action], value || '');
                break;

              case 'classList.add':
                target.classList.add(...[].concat(value).filter(Boolean));
                break;

              case 'classList.remove':
                target.classList.remove(...[].concat(value).filter(Boolean));
                break;

              case 'setAttribute':
                {
                    const [name, attrValue] = value || [];
                    if (name) {
                        target.setAttribute(name, attrValue ?? '');
                    }
                    break;
                }

              case 'removeAttribute':
                if (value) {
                    target.removeAttribute(value);
                }
                break;

              case 'notify':
                {
                    const [message, type = 'info', duration] = [].concat(value);
                    if (message) {
                        notify(message, type, duration);
                    }
                    break;
                }
            }
        }
    });
})();