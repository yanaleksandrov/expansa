(function() {
    document.addEventListener("youla:init", () => {
        const BYTES_IN_MB = 1048576;
        Youla.baseURL ??= "";
        Youla.method("ajax", (e, el) => (route, payload, onProgress, options = {}) => {
            abortPrevious(el);
            const xhr = el.__ajax = new XMLHttpRequest;
            const url = /^https?:\/\//.test(route) ? route : Youla.baseURL + route;
            const done = toggleLoading(el);
            xhr.open((el.getAttribute("method") || (el.tagName === "FORM" ? "POST" : "GET")).toUpperCase(), url);
            xhr.withCredentials = options.credentials ?? true;
            Object.entries(options.headers || {}).forEach(([name, value]) => xhr.setRequestHeader(name, value));
            xhr.onloadstart = xhr.upload.onprogress = event => onProgress?.(readProgress(event, xhr));
            xhr.onloadend = event => {
                onProgress?.(readProgress(event, xhr));
                done();
            };
            return new Promise((resolve, reject) => {
                xhr.__reject = reject;
                xhr.onerror = () => reject(new Error('Youla.js: "$ajax" network error.'));
                xhr.onload = () => {
                    const parsed = parseJSON(xhr.responseText);
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject(Object.assign(new Error(`Youla.js: "$ajax" failed with status ${xhr.status}.`), {
                            status: xhr.status,
                            data: parsed ?? xhr.responseText
                        }));
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
                                data: data,
                                el: el,
                                resolve: override
                            },
                            bubbles: true,
                            composed: true,
                            cancelable: true
                        }));
                        if (Array.isArray(data)) {
                            data.forEach(applyFragment);
                        }
                    } catch (error) {
                        console.error('Youla.js: "$ajax" fragment handling failed.', error);
                    }
                    if (!settled) {
                        resolve(data);
                    }
                };
                xhr.send(buildRequestBody(el, payload));
            });
        });
        function abortPrevious(el) {
            const xhr = el.__ajax;
            if (!xhr) {
                return;
            }
            xhr.onload = xhr.onerror = xhr.onloadstart = xhr.onloadend = xhr.upload.onprogress = null;
            xhr.abort();
            xhr.__reject?.(new DOMException('Superseded by a new "$ajax" call on the same element.', "AbortError"));
        }
        function toggleLoading(el) {
            const elements = [ el, ...el.querySelectorAll('[type="submit"]') ];
            elements.forEach(element => element.classList.add("is-load"));
            return () => elements.forEach(element => element.classList.remove("is-load"));
        }
        function buildRequestBody(el, payload) {
            const isForm = el.tagName === "FORM";
            const formData = isForm ? new FormData(el) : new FormData;
            if (!isForm && el.name) {
                if (el.type === "file") {
                    Array.from(el.files || []).forEach(file => formData.append(el.name, file));
                } else {
                    formData.append(el.name, el.value);
                }
            }
            if (payload && typeof payload === "object") {
                Object.entries(payload).forEach(([key, value]) => formData.append(key, value));
            }
            return formData;
        }
        function readProgress(event, xhr) {
            const {loaded: loaded = 0, total: total = 0, type: type} = event;
            const {responseText: raw = "", status: status = 0, responseURL: url = ""} = xhr;
            return {
                raw: raw,
                json: parseJSON(raw),
                blob: new Blob([ raw ]),
                status: status,
                url: url,
                loaded: toMegabytes(loaded),
                total: toMegabytes(total),
                percent: total > 0 ? Math.round(loaded / total * 100) : 0,
                start: type === "loadstart",
                progress: type === "progress",
                end: type === "loadend"
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
        function applyFragment(item) {
            const {target: target, ...actions} = item;
            const targets = target ? document.querySelectorAll(target) : [ null ];
            targets.forEach(target => {
                Object.entries(actions).forEach(([key, value]) => {
                    const [action, delay] = key.split(":");
                    setTimeout(() => runFragmentAction(action, target, value), Number(delay) || 0);
                });
            });
        }
        function runFragmentAction(action, target, value) {
            switch (action) {
              case "changeURL":
                window.history.pushState(null, "", value || "");
                break;

              case "redirect":
                window.location = value || "";
                break;

              case "reload":
                window.location.reload();
                break;

              case "scrollTo":
                window.scrollBy({
                    top: target.getBoundingClientRect().top,
                    behavior: "smooth"
                });
                break;

              case "scrollIntoView":
                target.scrollIntoView(value);
                break;

              case "value":
                target.value = value || "";
                target.dispatchEvent(new Event("input", {
                    bubbles: true
                }));
                break;

              case "update":
                target.innerHTML = value || "";
                break;

              case "replace":
                target.outerHTML = value || "";
                break;

              case "remove":
                target.remove();
                break;

              case "before":
              case "prepend":
              case "append":
              case "after":
                target.insertAdjacentHTML({
                    before: "beforebegin",
                    prepend: "afterbegin",
                    append: "beforeend",
                    after: "afterend"
                }[action], value || "");
                break;

              case "classList.add":
                target.classList.add(value || "");
                break;

              case "classList.remove":
                target.classList.remove(value || "");
                break;

              case "setAttribute":
                {
                    const [name, attrValue] = value || [];
                    if (name) {
                        target.setAttribute(name, attrValue || "");
                    }
                    break;
                }

              case "removeAttribute":
                target.removeAttribute(value || "");
                break;

              case "notify":
                if (value) {
                    document.dispatchEvent(new CustomEvent("ajax:notify", {
                        detail: value,
                        bubbles: true
                    }));
                }
                break;
            }
        }
    });
})();