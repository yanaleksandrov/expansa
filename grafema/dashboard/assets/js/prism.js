var __webpack_modules__ = {
    491: (module, __unused_webpack_exports, __webpack_require__) => {
        var _self = typeof window !== 'undefined' ? window : typeof WorkerGlobalScope !== 'undefined' && self instanceof WorkerGlobalScope ? self : {};
        var Prism = function(_self) {
            var lang = /(?:^|\s)lang(?:uage)?-([\w-]+)(?=\s|$)/i;
            var uniqueId = 0;
            var plainTextGrammar = {};
            var _ = {
                manual: _self.Prism && _self.Prism.manual,
                disableWorkerMessageHandler: _self.Prism && _self.Prism.disableWorkerMessageHandler,
                util: {
                    encode: function encode(tokens) {
                        if (tokens instanceof Token) {
                            return new Token(tokens.type, encode(tokens.content), tokens.alias);
                        } else if (Array.isArray(tokens)) {
                            return tokens.map(encode);
                        } else {
                            return tokens.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\u00a0/g, ' ');
                        }
                    },
                    type: function(o) {
                        return Object.prototype.toString.call(o).slice(8, -1);
                    },
                    objId: function(obj) {
                        if (!obj['__id']) {
                            Object.defineProperty(obj, '__id', {
                                value: ++uniqueId
                            });
                        }
                        return obj['__id'];
                    },
                    clone: function deepClone(o, visited) {
                        visited = visited || {};
                        var clone;
                        var id;
                        switch (_.util.type(o)) {
                          case 'Object':
                            id = _.util.objId(o);
                            if (visited[id]) {
                                return visited[id];
                            }
                            clone = {};
                            visited[id] = clone;
                            for (var key in o) {
                                if (o.hasOwnProperty(key)) {
                                    clone[key] = deepClone(o[key], visited);
                                }
                            }
                            return clone;

                          case 'Array':
                            id = _.util.objId(o);
                            if (visited[id]) {
                                return visited[id];
                            }
                            clone = [];
                            visited[id] = clone;
                            o.forEach((function(v, i) {
                                clone[i] = deepClone(v, visited);
                            }));
                            return clone;

                          default:
                            return o;
                        }
                    },
                    getLanguage: function(element) {
                        while (element) {
                            var m = lang.exec(element.className);
                            if (m) {
                                return m[1].toLowerCase();
                            }
                            element = element.parentElement;
                        }
                        return 'none';
                    },
                    setLanguage: function(element, language) {
                        element.className = element.className.replace(RegExp(lang, 'gi'), '');
                        element.classList.add('language-' + language);
                    },
                    currentScript: function() {
                        if (typeof document === 'undefined') {
                            return null;
                        }
                        if ('currentScript' in document && 1 < 2) {
                            return document.currentScript;
                        }
                        try {
                            throw new Error;
                        } catch (err) {
                            var src = (/at [^(\r\n]*\((.*):[^:]+:[^:]+\)$/i.exec(err.stack) || [])[1];
                            if (src) {
                                var scripts = document.getElementsByTagName('script');
                                for (var i in scripts) {
                                    if (scripts[i].src == src) {
                                        return scripts[i];
                                    }
                                }
                            }
                            return null;
                        }
                    },
                    isActive: function(element, className, defaultActivation) {
                        var no = 'no-' + className;
                        while (element) {
                            var classList = element.classList;
                            if (classList.contains(className)) {
                                return true;
                            }
                            if (classList.contains(no)) {
                                return false;
                            }
                            element = element.parentElement;
                        }
                        return !!defaultActivation;
                    }
                },
                languages: {
                    plain: plainTextGrammar,
                    plaintext: plainTextGrammar,
                    text: plainTextGrammar,
                    txt: plainTextGrammar,
                    extend: function(id, redef) {
                        var lang = _.util.clone(_.languages[id]);
                        for (var key in redef) {
                            lang[key] = redef[key];
                        }
                        return lang;
                    },
                    insertBefore: function(inside, before, insert, root) {
                        root = root || _.languages;
                        var grammar = root[inside];
                        var ret = {};
                        for (var token in grammar) {
                            if (grammar.hasOwnProperty(token)) {
                                if (token == before) {
                                    for (var newToken in insert) {
                                        if (insert.hasOwnProperty(newToken)) {
                                            ret[newToken] = insert[newToken];
                                        }
                                    }
                                }
                                if (!insert.hasOwnProperty(token)) {
                                    ret[token] = grammar[token];
                                }
                            }
                        }
                        var old = root[inside];
                        root[inside] = ret;
                        _.languages.DFS(_.languages, (function(key, value) {
                            if (value === old && key != inside) {
                                this[key] = ret;
                            }
                        }));
                        return ret;
                    },
                    DFS: function DFS(o, callback, type, visited) {
                        visited = visited || {};
                        var objId = _.util.objId;
                        for (var i in o) {
                            if (o.hasOwnProperty(i)) {
                                callback.call(o, i, o[i], type || i);
                                var property = o[i];
                                var propertyType = _.util.type(property);
                                if (propertyType === 'Object' && !visited[objId(property)]) {
                                    visited[objId(property)] = true;
                                    DFS(property, callback, null, visited);
                                } else if (propertyType === 'Array' && !visited[objId(property)]) {
                                    visited[objId(property)] = true;
                                    DFS(property, callback, i, visited);
                                }
                            }
                        }
                    }
                },
                plugins: {},
                highlightAll: function(async, callback) {
                    _.highlightAllUnder(document, async, callback);
                },
                highlightAllUnder: function(container, async, callback) {
                    var env = {
                        callback,
                        container,
                        selector: 'code[class*="language-"], [class*="language-"] code, code[class*="lang-"], [class*="lang-"] code'
                    };
                    _.hooks.run('before-highlightall', env);
                    env.elements = Array.prototype.slice.apply(env.container.querySelectorAll(env.selector));
                    _.hooks.run('before-all-elements-highlight', env);
                    for (var i = 0, element; element = env.elements[i++]; ) {
                        _.highlightElement(element, async === true, env.callback);
                    }
                },
                highlightElement: function(element, async, callback) {
                    var language = _.util.getLanguage(element);
                    var grammar = _.languages[language];
                    _.util.setLanguage(element, language);
                    var parent = element.parentElement;
                    if (parent && parent.nodeName.toLowerCase() === 'pre') {
                        _.util.setLanguage(parent, language);
                    }
                    var code = element.textContent;
                    var env = {
                        element,
                        language,
                        grammar,
                        code
                    };
                    function insertHighlightedCode(highlightedCode) {
                        env.highlightedCode = highlightedCode;
                        _.hooks.run('before-insert', env);
                        env.element.innerHTML = env.highlightedCode;
                        _.hooks.run('after-highlight', env);
                        _.hooks.run('complete', env);
                        callback && callback.call(env.element);
                    }
                    _.hooks.run('before-sanity-check', env);
                    parent = env.element.parentElement;
                    if (parent && parent.nodeName.toLowerCase() === 'pre' && !parent.hasAttribute('tabindex')) {
                        parent.setAttribute('tabindex', '0');
                    }
                    if (!env.code) {
                        _.hooks.run('complete', env);
                        callback && callback.call(env.element);
                        return;
                    }
                    _.hooks.run('before-highlight', env);
                    if (!env.grammar) {
                        insertHighlightedCode(_.util.encode(env.code));
                        return;
                    }
                    if (async && _self.Worker) {
                        var worker = new Worker(_.filename);
                        worker.onmessage = function(evt) {
                            insertHighlightedCode(evt.data);
                        };
                        worker.postMessage(JSON.stringify({
                            language: env.language,
                            code: env.code,
                            immediateClose: true
                        }));
                    } else {
                        insertHighlightedCode(_.highlight(env.code, env.grammar, env.language));
                    }
                },
                highlight: function(text, grammar, language) {
                    var env = {
                        code: text,
                        grammar,
                        language
                    };
                    _.hooks.run('before-tokenize', env);
                    if (!env.grammar) {
                        throw new Error('The language "' + env.language + '" has no grammar.');
                    }
                    env.tokens = _.tokenize(env.code, env.grammar);
                    _.hooks.run('after-tokenize', env);
                    return Token.stringify(_.util.encode(env.tokens), env.language);
                },
                tokenize: function(text, grammar) {
                    var rest = grammar.rest;
                    if (rest) {
                        for (var token in rest) {
                            grammar[token] = rest[token];
                        }
                        delete grammar.rest;
                    }
                    var tokenList = new LinkedList;
                    addAfter(tokenList, tokenList.head, text);
                    matchGrammar(text, tokenList, grammar, tokenList.head, 0);
                    return toArray(tokenList);
                },
                hooks: {
                    all: {},
                    add: function(name, callback) {
                        var hooks = _.hooks.all;
                        hooks[name] = hooks[name] || [];
                        hooks[name].push(callback);
                    },
                    run: function(name, env) {
                        var callbacks = _.hooks.all[name];
                        if (!callbacks || !callbacks.length) {
                            return;
                        }
                        for (var i = 0, callback; callback = callbacks[i++]; ) {
                            callback(env);
                        }
                    }
                },
                Token
            };
            _self.Prism = _;
            function Token(type, content, alias, matchedStr) {
                this.type = type;
                this.content = content;
                this.alias = alias;
                this.length = (matchedStr || '').length | 0;
            }
            Token.stringify = function stringify(o, language) {
                if (typeof o == 'string') {
                    return o;
                }
                if (Array.isArray(o)) {
                    var s = '';
                    o.forEach((function(e) {
                        s += stringify(e, language);
                    }));
                    return s;
                }
                var env = {
                    type: o.type,
                    content: stringify(o.content, language),
                    tag: 'span',
                    classes: [ 'token', o.type ],
                    attributes: {},
                    language
                };
                var aliases = o.alias;
                if (aliases) {
                    if (Array.isArray(aliases)) {
                        Array.prototype.push.apply(env.classes, aliases);
                    } else {
                        env.classes.push(aliases);
                    }
                }
                _.hooks.run('wrap', env);
                var attributes = '';
                for (var name in env.attributes) {
                    attributes += ' ' + name + '="' + (env.attributes[name] || '').replace(/"/g, '&quot;') + '"';
                }
                return '<' + env.tag + ' class="' + env.classes.join(' ') + '"' + attributes + '>' + env.content + '</' + env.tag + '>';
            };
            function matchPattern(pattern, pos, text, lookbehind) {
                pattern.lastIndex = pos;
                var match = pattern.exec(text);
                if (match && lookbehind && match[1]) {
                    var lookbehindLength = match[1].length;
                    match.index += lookbehindLength;
                    match[0] = match[0].slice(lookbehindLength);
                }
                return match;
            }
            function matchGrammar(text, tokenList, grammar, startNode, startPos, rematch) {
                for (var token in grammar) {
                    if (!grammar.hasOwnProperty(token) || !grammar[token]) {
                        continue;
                    }
                    var patterns = grammar[token];
                    patterns = Array.isArray(patterns) ? patterns : [ patterns ];
                    for (var j = 0; j < patterns.length; ++j) {
                        if (rematch && rematch.cause == token + ',' + j) {
                            return;
                        }
                        var patternObj = patterns[j];
                        var inside = patternObj.inside;
                        var lookbehind = !!patternObj.lookbehind;
                        var greedy = !!patternObj.greedy;
                        var alias = patternObj.alias;
                        if (greedy && !patternObj.pattern.global) {
                            var flags = patternObj.pattern.toString().match(/[imsuy]*$/)[0];
                            patternObj.pattern = RegExp(patternObj.pattern.source, flags + 'g');
                        }
                        var pattern = patternObj.pattern || patternObj;
                        for (var currentNode = startNode.next, pos = startPos; currentNode !== tokenList.tail; pos += currentNode.value.length, 
                        currentNode = currentNode.next) {
                            if (rematch && pos >= rematch.reach) {
                                break;
                            }
                            var str = currentNode.value;
                            if (tokenList.length > text.length) {
                                return;
                            }
                            if (str instanceof Token) {
                                continue;
                            }
                            var removeCount = 1;
                            var match;
                            if (greedy) {
                                match = matchPattern(pattern, pos, text, lookbehind);
                                if (!match || match.index >= text.length) {
                                    break;
                                }
                                var from = match.index;
                                var to = match.index + match[0].length;
                                var p = pos;
                                p += currentNode.value.length;
                                while (from >= p) {
                                    currentNode = currentNode.next;
                                    p += currentNode.value.length;
                                }
                                p -= currentNode.value.length;
                                pos = p;
                                if (currentNode.value instanceof Token) {
                                    continue;
                                }
                                for (var k = currentNode; k !== tokenList.tail && (p < to || typeof k.value === 'string'); k = k.next) {
                                    removeCount++;
                                    p += k.value.length;
                                }
                                removeCount--;
                                str = text.slice(pos, p);
                                match.index -= pos;
                            } else {
                                match = matchPattern(pattern, 0, str, lookbehind);
                                if (!match) {
                                    continue;
                                }
                            }
                            var from = match.index;
                            var matchStr = match[0];
                            var before = str.slice(0, from);
                            var after = str.slice(from + matchStr.length);
                            var reach = pos + str.length;
                            if (rematch && reach > rematch.reach) {
                                rematch.reach = reach;
                            }
                            var removeFrom = currentNode.prev;
                            if (before) {
                                removeFrom = addAfter(tokenList, removeFrom, before);
                                pos += before.length;
                            }
                            removeRange(tokenList, removeFrom, removeCount);
                            var wrapped = new Token(token, inside ? _.tokenize(matchStr, inside) : matchStr, alias, matchStr);
                            currentNode = addAfter(tokenList, removeFrom, wrapped);
                            if (after) {
                                addAfter(tokenList, currentNode, after);
                            }
                            if (removeCount > 1) {
                                var nestedRematch = {
                                    cause: token + ',' + j,
                                    reach
                                };
                                matchGrammar(text, tokenList, grammar, currentNode.prev, pos, nestedRematch);
                                if (rematch && nestedRematch.reach > rematch.reach) {
                                    rematch.reach = nestedRematch.reach;
                                }
                            }
                        }
                    }
                }
            }
            function LinkedList() {
                var head = {
                    value: null,
                    prev: null,
                    next: null
                };
                var tail = {
                    value: null,
                    prev: head,
                    next: null
                };
                head.next = tail;
                this.head = head;
                this.tail = tail;
                this.length = 0;
            }
            function addAfter(list, node, value) {
                var next = node.next;
                var newNode = {
                    value,
                    prev: node,
                    next
                };
                node.next = newNode;
                next.prev = newNode;
                list.length++;
                return newNode;
            }
            function removeRange(list, node, count) {
                var next = node.next;
                for (var i = 0; i < count && next !== list.tail; i++) {
                    next = next.next;
                }
                node.next = next;
                next.prev = node;
                list.length -= i;
            }
            function toArray(list) {
                var array = [];
                var node = list.head.next;
                while (node !== list.tail) {
                    array.push(node.value);
                    node = node.next;
                }
                return array;
            }
            if (!_self.document) {
                if (!_self.addEventListener) {
                    return _;
                }
                if (!_.disableWorkerMessageHandler) {
                    _self.addEventListener('message', (function(evt) {
                        var message = JSON.parse(evt.data);
                        var lang = message.language;
                        var code = message.code;
                        var immediateClose = message.immediateClose;
                        _self.postMessage(_.highlight(code, _.languages[lang], lang));
                        if (immediateClose) {
                            _self.close();
                        }
                    }), false);
                }
                return _;
            }
            var script = _.util.currentScript();
            if (script) {
                _.filename = script.src;
                if (script.hasAttribute('data-manual')) {
                    _.manual = true;
                }
            }
            function highlightAutomaticallyCallback() {
                if (!_.manual) {
                    _.highlightAll();
                }
            }
            if (!_.manual) {
                var readyState = document.readyState;
                if (readyState === 'loading' || readyState === 'interactive' && script && script.defer) {
                    document.addEventListener('DOMContentLoaded', highlightAutomaticallyCallback);
                } else {
                    if (window.requestAnimationFrame) {
                        window.requestAnimationFrame(highlightAutomaticallyCallback);
                    } else {
                        window.setTimeout(highlightAutomaticallyCallback, 16);
                    }
                }
            }
            return _;
        }(_self);
        if (true && module.exports) {
            module.exports = Prism;
        }
        if (typeof __webpack_require__.g !== 'undefined') {
            __webpack_require__.g.Prism = Prism;
        }
        Prism.languages.markup = {
            comment: {
                pattern: /<!--(?:(?!<!--)[\s\S])*?-->/,
                greedy: true
            },
            prolog: {
                pattern: /<\?[\s\S]+?\?>/,
                greedy: true
            },
            doctype: {
                pattern: /<!DOCTYPE(?:[^>"'[\]]|"[^"]*"|'[^']*')+(?:\[(?:[^<"'\]]|"[^"]*"|'[^']*'|<(?!!--)|<!--(?:[^-]|-(?!->))*-->)*\]\s*)?>/i,
                greedy: true,
                inside: {
                    'internal-subset': {
                        pattern: /(^[^\[]*\[)[\s\S]+(?=\]>$)/,
                        lookbehind: true,
                        greedy: true,
                        inside: null
                    },
                    string: {
                        pattern: /"[^"]*"|'[^']*'/,
                        greedy: true
                    },
                    punctuation: /^<!|>$|[[\]]/,
                    'doctype-tag': /^DOCTYPE/i,
                    name: /[^\s<>'"]+/
                }
            },
            cdata: {
                pattern: /<!\[CDATA\[[\s\S]*?\]\]>/i,
                greedy: true
            },
            tag: {
                pattern: /<\/?(?!\d)[^\s>\/=$<%]+(?:\s(?:\s*[^\s>\/=]+(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s'">=]+(?=[\s>]))|(?=[\s/>])))+)?\s*\/?>/,
                greedy: true,
                inside: {
                    tag: {
                        pattern: /^<\/?[^\s>\/]+/,
                        inside: {
                            punctuation: /^<\/?/,
                            namespace: /^[^\s>\/:]+:/
                        }
                    },
                    'special-attr': [],
                    'attr-value': {
                        pattern: /=\s*(?:"[^"]*"|'[^']*'|[^\s'">=]+)/,
                        inside: {
                            punctuation: [ {
                                pattern: /^=/,
                                alias: 'attr-equals'
                            }, {
                                pattern: /^(\s*)["']|["']$/,
                                lookbehind: true
                            } ]
                        }
                    },
                    punctuation: /\/?>/,
                    'attr-name': {
                        pattern: /[^\s>\/]+/,
                        inside: {
                            namespace: /^[^\s>\/:]+:/
                        }
                    }
                }
            },
            entity: [ {
                pattern: /&[\da-z]{1,8};/i,
                alias: 'named-entity'
            }, /&#x?[\da-f]{1,8};/i ]
        };
        Prism.languages.markup['tag'].inside['attr-value'].inside['entity'] = Prism.languages.markup['entity'];
        Prism.languages.markup['doctype'].inside['internal-subset'].inside = Prism.languages.markup;
        Prism.hooks.add('wrap', (function(env) {
            if (env.type === 'entity') {
                env.attributes['title'] = env.content.replace(/&amp;/, '&');
            }
        }));
        Object.defineProperty(Prism.languages.markup.tag, 'addInlined', {
            value: function addInlined(tagName, lang) {
                var includedCdataInside = {};
                includedCdataInside['language-' + lang] = {
                    pattern: /(^<!\[CDATA\[)[\s\S]+?(?=\]\]>$)/i,
                    lookbehind: true,
                    inside: Prism.languages[lang]
                };
                includedCdataInside['cdata'] = /^<!\[CDATA\[|\]\]>$/i;
                var inside = {
                    'included-cdata': {
                        pattern: /<!\[CDATA\[[\s\S]*?\]\]>/i,
                        inside: includedCdataInside
                    }
                };
                inside['language-' + lang] = {
                    pattern: /[\s\S]+/,
                    inside: Prism.languages[lang]
                };
                var def = {};
                def[tagName] = {
                    pattern: RegExp(/(<__[^>]*>)(?:<!\[CDATA\[(?:[^\]]|\](?!\]>))*\]\]>|(?!<!\[CDATA\[)[\s\S])*?(?=<\/__>)/.source.replace(/__/g, (function() {
                        return tagName;
                    })), 'i'),
                    lookbehind: true,
                    greedy: true,
                    inside
                };
                Prism.languages.insertBefore('markup', 'cdata', def);
            }
        });
        Object.defineProperty(Prism.languages.markup.tag, 'addAttribute', {
            value: function(attrName, lang) {
                Prism.languages.markup.tag.inside['special-attr'].push({
                    pattern: RegExp(/(^|["'\s])/.source + '(?:' + attrName + ')' + /\s*=\s*(?:"[^"]*"|'[^']*'|[^\s'">=]+(?=[\s>]))/.source, 'i'),
                    lookbehind: true,
                    inside: {
                        'attr-name': /^[^\s=]+/,
                        'attr-value': {
                            pattern: /=[\s\S]+/,
                            inside: {
                                value: {
                                    pattern: /(^=\s*(["']|(?!["'])))\S[\s\S]*(?=\2$)/,
                                    lookbehind: true,
                                    alias: [ lang, 'language-' + lang ],
                                    inside: Prism.languages[lang]
                                },
                                punctuation: [ {
                                    pattern: /^=/,
                                    alias: 'attr-equals'
                                }, /"|'/ ]
                            }
                        }
                    }
                });
            }
        });
        Prism.languages.html = Prism.languages.markup;
        Prism.languages.mathml = Prism.languages.markup;
        Prism.languages.svg = Prism.languages.markup;
        Prism.languages.xml = Prism.languages.extend('markup', {});
        Prism.languages.ssml = Prism.languages.xml;
        Prism.languages.atom = Prism.languages.xml;
        Prism.languages.rss = Prism.languages.xml;
        (function(Prism) {
            var string = /(?:"(?:\\(?:\r\n|[\s\S])|[^"\\\r\n])*"|'(?:\\(?:\r\n|[\s\S])|[^'\\\r\n])*')/;
            Prism.languages.css = {
                comment: /\/\*[\s\S]*?\*\//,
                atrule: {
                    pattern: RegExp('@[\\w-](?:' + /[^;{\s"']|\s+(?!\s)/.source + '|' + string.source + ')*?' + /(?:;|(?=\s*\{))/.source),
                    inside: {
                        rule: /^@[\w-]+/,
                        'selector-function-argument': {
                            pattern: /(\bselector\s*\(\s*(?![\s)]))(?:[^()\s]|\s+(?![\s)])|\((?:[^()]|\([^()]*\))*\))+(?=\s*\))/,
                            lookbehind: true,
                            alias: 'selector'
                        },
                        keyword: {
                            pattern: /(^|[^\w-])(?:and|not|only|or)(?![\w-])/,
                            lookbehind: true
                        }
                    }
                },
                url: {
                    pattern: RegExp('\\burl\\((?:' + string.source + '|' + /(?:[^\\\r\n()"']|\\[\s\S])*/.source + ')\\)', 'i'),
                    greedy: true,
                    inside: {
                        function: /^url/i,
                        punctuation: /^\(|\)$/,
                        string: {
                            pattern: RegExp('^' + string.source + '$'),
                            alias: 'url'
                        }
                    }
                },
                selector: {
                    pattern: RegExp('(^|[{}\\s])[^{}\\s](?:[^{};"\'\\s]|\\s+(?![\\s{])|' + string.source + ')*(?=\\s*\\{)'),
                    lookbehind: true
                },
                string: {
                    pattern: string,
                    greedy: true
                },
                property: {
                    pattern: /(^|[^-\w\xA0-\uFFFF])(?!\s)[-_a-z\xA0-\uFFFF](?:(?!\s)[-\w\xA0-\uFFFF])*(?=\s*:)/i,
                    lookbehind: true
                },
                important: /!important\b/i,
                function: {
                    pattern: /(^|[^-a-z0-9])[-a-z0-9]+(?=\()/i,
                    lookbehind: true
                },
                punctuation: /[(){};:,]/
            };
            Prism.languages.css['atrule'].inside.rest = Prism.languages.css;
            var markup = Prism.languages.markup;
            if (markup) {
                markup.tag.addInlined('style', 'css');
                markup.tag.addAttribute('style', 'css');
            }
        })(Prism);
        Prism.languages.clike = {
            comment: [ {
                pattern: /(^|[^\\])\/\*[\s\S]*?(?:\*\/|$)/,
                lookbehind: true,
                greedy: true
            }, {
                pattern: /(^|[^\\:])\/\/.*/,
                lookbehind: true,
                greedy: true
            } ],
            string: {
                pattern: /(["'])(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/,
                greedy: true
            },
            'class-name': {
                pattern: /(\b(?:class|extends|implements|instanceof|interface|new|trait)\s+|\bcatch\s+\()[\w.\\]+/i,
                lookbehind: true,
                inside: {
                    punctuation: /[.\\]/
                }
            },
            keyword: /\b(?:break|catch|continue|do|else|finally|for|function|if|in|instanceof|new|null|return|throw|try|while)\b/,
            boolean: /\b(?:false|true)\b/,
            function: /\b\w+(?=\()/,
            number: /\b0x[\da-f]+\b|(?:\b\d+(?:\.\d*)?|\B\.\d+)(?:e[+-]?\d+)?/i,
            operator: /[<>]=?|[!=]=?=?|--?|\+\+?|&&?|\|\|?|[?*/~^%]/,
            punctuation: /[{}[\];(),.:]/
        };
        Prism.languages.javascript = Prism.languages.extend('clike', {
            'class-name': [ Prism.languages.clike['class-name'], {
                pattern: /(^|[^$\w\xA0-\uFFFF])(?!\s)[_$A-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?=\.(?:constructor|prototype))/,
                lookbehind: true
            } ],
            keyword: [ {
                pattern: /((?:^|\})\s*)catch\b/,
                lookbehind: true
            }, {
                pattern: /(^|[^.]|\.\.\.\s*)\b(?:as|assert(?=\s*\{)|async(?=\s*(?:function\b|\(|[$\w\xA0-\uFFFF]|$))|await|break|case|class|const|continue|debugger|default|delete|do|else|enum|export|extends|finally(?=\s*(?:\{|$))|for|from(?=\s*(?:['"]|$))|function|(?:get|set)(?=\s*(?:[#\[$\w\xA0-\uFFFF]|$))|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|static|super|switch|this|throw|try|typeof|undefined|var|void|while|with|yield)\b/,
                lookbehind: true
            } ],
            function: /#?(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?=\s*(?:\.\s*(?:apply|bind|call)\s*)?\()/,
            number: {
                pattern: RegExp(/(^|[^\w$])/.source + '(?:' + (/NaN|Infinity/.source + '|' + /0[bB][01]+(?:_[01]+)*n?/.source + '|' + /0[oO][0-7]+(?:_[0-7]+)*n?/.source + '|' + /0[xX][\dA-Fa-f]+(?:_[\dA-Fa-f]+)*n?/.source + '|' + /\d+(?:_\d+)*n/.source + '|' + /(?:\d+(?:_\d+)*(?:\.(?:\d+(?:_\d+)*)?)?|\.\d+(?:_\d+)*)(?:[Ee][+-]?\d+(?:_\d+)*)?/.source) + ')' + /(?![\w$])/.source),
                lookbehind: true
            },
            operator: /--|\+\+|\*\*=?|=>|&&=?|\|\|=?|[!=]==|<<=?|>>>?=?|[-+*/%&|^!=<>]=?|\.{3}|\?\?=?|\?\.?|[~:]/
        });
        Prism.languages.javascript['class-name'][0].pattern = /(\b(?:class|extends|implements|instanceof|interface|new)\s+)[\w.\\]+/;
        Prism.languages.insertBefore('javascript', 'keyword', {
            regex: {
                pattern: RegExp(/((?:^|[^$\w\xA0-\uFFFF."'\])\s]|\b(?:return|yield))\s*)/.source + /\//.source + '(?:' + /(?:\[(?:[^\]\\\r\n]|\\.)*\]|\\.|[^/\\\[\r\n])+\/[dgimyus]{0,7}/.source + '|' + /(?:\[(?:[^[\]\\\r\n]|\\.|\[(?:[^[\]\\\r\n]|\\.|\[(?:[^[\]\\\r\n]|\\.)*\])*\])*\]|\\.|[^/\\\[\r\n])+\/[dgimyus]{0,7}v[dgimyus]{0,7}/.source + ')' + /(?=(?:\s|\/\*(?:[^*]|\*(?!\/))*\*\/)*(?:$|[\r\n,.;:})\]]|\/\/))/.source),
                lookbehind: true,
                greedy: true,
                inside: {
                    'regex-source': {
                        pattern: /^(\/)[\s\S]+(?=\/[a-z]*$)/,
                        lookbehind: true,
                        alias: 'language-regex',
                        inside: Prism.languages.regex
                    },
                    'regex-delimiter': /^\/|\/$/,
                    'regex-flags': /^[a-z]+$/
                }
            },
            'function-variable': {
                pattern: /#?(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?=\s*[=:]\s*(?:async\s*)?(?:\bfunction\b|(?:\((?:[^()]|\([^()]*\))*\)|(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*)\s*=>))/,
                alias: 'function'
            },
            parameter: [ {
                pattern: /(function(?:\s+(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*)?\s*\(\s*)(?!\s)(?:[^()\s]|\s+(?![\s)])|\([^()]*\))+(?=\s*\))/,
                lookbehind: true,
                inside: Prism.languages.javascript
            }, {
                pattern: /(^|[^$\w\xA0-\uFFFF])(?!\s)[_$a-z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?=\s*=>)/i,
                lookbehind: true,
                inside: Prism.languages.javascript
            }, {
                pattern: /(\(\s*)(?!\s)(?:[^()\s]|\s+(?![\s)])|\([^()]*\))+(?=\s*\)\s*=>)/,
                lookbehind: true,
                inside: Prism.languages.javascript
            }, {
                pattern: /((?:\b|\s|^)(?!(?:as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|enum|export|extends|finally|for|from|function|get|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|set|static|super|switch|this|throw|try|typeof|undefined|var|void|while|with|yield)(?![$\w\xA0-\uFFFF]))(?:(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*\s*)\(\s*|\]\s*\(\s*)(?!\s)(?:[^()\s]|\s+(?![\s)])|\([^()]*\))+(?=\s*\)\s*\{)/,
                lookbehind: true,
                inside: Prism.languages.javascript
            } ],
            constant: /\b[A-Z](?:[A-Z_]|\dx?)*\b/
        });
        Prism.languages.insertBefore('javascript', 'string', {
            hashbang: {
                pattern: /^#!.*/,
                greedy: true,
                alias: 'comment'
            },
            'template-string': {
                pattern: /`(?:\\[\s\S]|\$\{(?:[^{}]|\{(?:[^{}]|\{[^}]*\})*\})+\}|(?!\$\{)[^\\`])*`/,
                greedy: true,
                inside: {
                    'template-punctuation': {
                        pattern: /^`|`$/,
                        alias: 'string'
                    },
                    interpolation: {
                        pattern: /((?:^|[^\\])(?:\\{2})*)\$\{(?:[^{}]|\{(?:[^{}]|\{[^}]*\})*\})+\}/,
                        lookbehind: true,
                        inside: {
                            'interpolation-punctuation': {
                                pattern: /^\$\{|\}$/,
                                alias: 'punctuation'
                            },
                            rest: Prism.languages.javascript
                        }
                    },
                    string: /[\s\S]+/
                }
            },
            'string-property': {
                pattern: /((?:^|[,{])[ \t]*)(["'])(?:\\(?:\r\n|[\s\S])|(?!\2)[^\\\r\n])*\2(?=\s*:)/m,
                lookbehind: true,
                greedy: true,
                alias: 'property'
            }
        });
        Prism.languages.insertBefore('javascript', 'operator', {
            'literal-property': {
                pattern: /((?:^|[,{])[ \t]*)(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?=\s*:)/m,
                lookbehind: true,
                alias: 'property'
            }
        });
        if (Prism.languages.markup) {
            Prism.languages.markup.tag.addInlined('script', 'javascript');
            Prism.languages.markup.tag.addAttribute(/on(?:abort|blur|change|click|composition(?:end|start|update)|dblclick|error|focus(?:in|out)?|key(?:down|up)|load|mouse(?:down|enter|leave|move|out|over|up)|reset|resize|scroll|select|slotchange|submit|unload|wheel)/.source, 'javascript');
        }
        Prism.languages.js = Prism.languages.javascript;
        (function(Prism) {
            var string = /("|')(?:\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/;
            var selectorInside;
            Prism.languages.css.selector = {
                pattern: Prism.languages.css.selector.pattern,
                lookbehind: true,
                inside: selectorInside = {
                    'pseudo-element': /:(?:after|before|first-letter|first-line|selection)|::[-\w]+/,
                    'pseudo-class': /:[-\w]+/,
                    class: /\.[-\w]+/,
                    id: /#[-\w]+/,
                    attribute: {
                        pattern: RegExp('\\[(?:[^[\\]"\']|' + string.source + ')*\\]'),
                        greedy: true,
                        inside: {
                            punctuation: /^\[|\]$/,
                            'case-sensitivity': {
                                pattern: /(\s)[si]$/i,
                                lookbehind: true,
                                alias: 'keyword'
                            },
                            namespace: {
                                pattern: /^(\s*)(?:(?!\s)[-*\w\xA0-\uFFFF])*\|(?!=)/,
                                lookbehind: true,
                                inside: {
                                    punctuation: /\|$/
                                }
                            },
                            'attr-name': {
                                pattern: /^(\s*)(?:(?!\s)[-\w\xA0-\uFFFF])+/,
                                lookbehind: true
                            },
                            'attr-value': [ string, {
                                pattern: /(=\s*)(?:(?!\s)[-\w\xA0-\uFFFF])+(?=\s*$)/,
                                lookbehind: true
                            } ],
                            operator: /[|~*^$]?=/
                        }
                    },
                    'n-th': [ {
                        pattern: /(\(\s*)[+-]?\d*[\dn](?:\s*[+-]\s*\d+)?(?=\s*\))/,
                        lookbehind: true,
                        inside: {
                            number: /[\dn]+/,
                            operator: /[+-]/
                        }
                    }, {
                        pattern: /(\(\s*)(?:even|odd)(?=\s*\))/i,
                        lookbehind: true
                    } ],
                    combinator: />|\+|~|\|\|/,
                    punctuation: /[(),]/
                }
            };
            Prism.languages.css['atrule'].inside['selector-function-argument'].inside = selectorInside;
            Prism.languages.insertBefore('css', 'property', {
                variable: {
                    pattern: /(^|[^-\w\xA0-\uFFFF])--(?!\s)[-_a-z\xA0-\uFFFF](?:(?!\s)[-\w\xA0-\uFFFF])*/i,
                    lookbehind: true
                }
            });
            var unit = {
                pattern: /(\b\d+)(?:%|[a-z]+(?![\w-]))/,
                lookbehind: true
            };
            var number = {
                pattern: /(^|[^\w.-])-?(?:\d+(?:\.\d+)?|\.\d+)/,
                lookbehind: true
            };
            Prism.languages.insertBefore('css', 'function', {
                operator: {
                    pattern: /(\s)[+\-*\/](?=\s)/,
                    lookbehind: true
                },
                hexcode: {
                    pattern: /\B#[\da-f]{3,8}\b/i,
                    alias: 'color'
                },
                color: [ {
                    pattern: /(^|[^\w-])(?:AliceBlue|AntiqueWhite|Aqua|Aquamarine|Azure|Beige|Bisque|Black|BlanchedAlmond|Blue|BlueViolet|Brown|BurlyWood|CadetBlue|Chartreuse|Chocolate|Coral|CornflowerBlue|Cornsilk|Crimson|Cyan|DarkBlue|DarkCyan|DarkGoldenRod|DarkGr[ae]y|DarkGreen|DarkKhaki|DarkMagenta|DarkOliveGreen|DarkOrange|DarkOrchid|DarkRed|DarkSalmon|DarkSeaGreen|DarkSlateBlue|DarkSlateGr[ae]y|DarkTurquoise|DarkViolet|DeepPink|DeepSkyBlue|DimGr[ae]y|DodgerBlue|FireBrick|FloralWhite|ForestGreen|Fuchsia|Gainsboro|GhostWhite|Gold|GoldenRod|Gr[ae]y|Green|GreenYellow|HoneyDew|HotPink|IndianRed|Indigo|Ivory|Khaki|Lavender|LavenderBlush|LawnGreen|LemonChiffon|LightBlue|LightCoral|LightCyan|LightGoldenRodYellow|LightGr[ae]y|LightGreen|LightPink|LightSalmon|LightSeaGreen|LightSkyBlue|LightSlateGr[ae]y|LightSteelBlue|LightYellow|Lime|LimeGreen|Linen|Magenta|Maroon|MediumAquaMarine|MediumBlue|MediumOrchid|MediumPurple|MediumSeaGreen|MediumSlateBlue|MediumSpringGreen|MediumTurquoise|MediumVioletRed|MidnightBlue|MintCream|MistyRose|Moccasin|NavajoWhite|Navy|OldLace|Olive|OliveDrab|Orange|OrangeRed|Orchid|PaleGoldenRod|PaleGreen|PaleTurquoise|PaleVioletRed|PapayaWhip|PeachPuff|Peru|Pink|Plum|PowderBlue|Purple|RebeccaPurple|Red|RosyBrown|RoyalBlue|SaddleBrown|Salmon|SandyBrown|SeaGreen|SeaShell|Sienna|Silver|SkyBlue|SlateBlue|SlateGr[ae]y|Snow|SpringGreen|SteelBlue|Tan|Teal|Thistle|Tomato|Transparent|Turquoise|Violet|Wheat|White|WhiteSmoke|Yellow|YellowGreen)(?![\w-])/i,
                    lookbehind: true
                }, {
                    pattern: /\b(?:hsl|rgb)\(\s*\d{1,3}\s*,\s*\d{1,3}%?\s*,\s*\d{1,3}%?\s*\)\B|\b(?:hsl|rgb)a\(\s*\d{1,3}\s*,\s*\d{1,3}%?\s*,\s*\d{1,3}%?\s*,\s*(?:0|0?\.\d+|1)\s*\)\B/i,
                    inside: {
                        unit,
                        number,
                        function: /[\w-]+(?=\()/,
                        punctuation: /[(),]/
                    }
                } ],
                entity: /\\[\da-f]{1,8}/i,
                unit,
                number
            });
        })(Prism);
        Prism.languages.csv = {
            value: /[^\r\n,"]+|"(?:[^"]|"")*"(?!")/,
            punctuation: /,/
        };
        (function(Prism) {
            function getPlaceholder(language, index) {
                return '___' + language.toUpperCase() + index + '___';
            }
            Object.defineProperties(Prism.languages['markup-templating'] = {}, {
                buildPlaceholders: {
                    value: function(env, language, placeholderPattern, replaceFilter) {
                        if (env.language !== language) {
                            return;
                        }
                        var tokenStack = env.tokenStack = [];
                        env.code = env.code.replace(placeholderPattern, (function(match) {
                            if (typeof replaceFilter === 'function' && !replaceFilter(match)) {
                                return match;
                            }
                            var i = tokenStack.length;
                            var placeholder;
                            while (env.code.indexOf(placeholder = getPlaceholder(language, i)) !== -1) {
                                ++i;
                            }
                            tokenStack[i] = match;
                            return placeholder;
                        }));
                        env.grammar = Prism.languages.markup;
                    }
                },
                tokenizePlaceholders: {
                    value: function(env, language) {
                        if (env.language !== language || !env.tokenStack) {
                            return;
                        }
                        env.grammar = Prism.languages[language];
                        var j = 0;
                        var keys = Object.keys(env.tokenStack);
                        function walkTokens(tokens) {
                            for (var i = 0; i < tokens.length; i++) {
                                if (j >= keys.length) {
                                    break;
                                }
                                var token = tokens[i];
                                if (typeof token === 'string' || token.content && typeof token.content === 'string') {
                                    var k = keys[j];
                                    var t = env.tokenStack[k];
                                    var s = typeof token === 'string' ? token : token.content;
                                    var placeholder = getPlaceholder(language, k);
                                    var index = s.indexOf(placeholder);
                                    if (index > -1) {
                                        ++j;
                                        var before = s.substring(0, index);
                                        var middle = new Prism.Token(language, Prism.tokenize(t, env.grammar), 'language-' + language, t);
                                        var after = s.substring(index + placeholder.length);
                                        var replacement = [];
                                        if (before) {
                                            replacement.push.apply(replacement, walkTokens([ before ]));
                                        }
                                        replacement.push(middle);
                                        if (after) {
                                            replacement.push.apply(replacement, walkTokens([ after ]));
                                        }
                                        if (typeof token === 'string') {
                                            tokens.splice.apply(tokens, [ i, 1 ].concat(replacement));
                                        } else {
                                            token.content = replacement;
                                        }
                                    }
                                } else if (token.content) {
                                    walkTokens(token.content);
                                }
                            }
                            return tokens;
                        }
                        walkTokens(env.tokens);
                    }
                }
            });
        })(Prism);
        (function(Prism) {
            var comment = /\/\*[\s\S]*?\*\/|\/\/.*|#(?!\[).*/;
            var constant = [ {
                pattern: /\b(?:false|true)\b/i,
                alias: 'boolean'
            }, {
                pattern: /(::\s*)\b[a-z_]\w*\b(?!\s*\()/i,
                greedy: true,
                lookbehind: true
            }, {
                pattern: /(\b(?:case|const)\s+)\b[a-z_]\w*(?=\s*[;=])/i,
                greedy: true,
                lookbehind: true
            }, /\b(?:null)\b/i, /\b[A-Z_][A-Z0-9_]*\b(?!\s*\()/ ];
            var number = /\b0b[01]+(?:_[01]+)*\b|\b0o[0-7]+(?:_[0-7]+)*\b|\b0x[\da-f]+(?:_[\da-f]+)*\b|(?:\b\d+(?:_\d+)*\.?(?:\d+(?:_\d+)*)?|\B\.\d+)(?:e[+-]?\d+)?/i;
            var operator = /<?=>|\?\?=?|\.{3}|\??->|[!=]=?=?|::|\*\*=?|--|\+\+|&&|\|\||<<|>>|[?~]|[/^|%*&<>.+-]=?/;
            var punctuation = /[{}\[\](),:;]/;
            Prism.languages.php = {
                delimiter: {
                    pattern: /\?>$|^<\?(?:php(?=\s)|=)?/i,
                    alias: 'important'
                },
                comment,
                variable: /\$+(?:\w+\b|(?=\{))/,
                package: {
                    pattern: /(namespace\s+|use\s+(?:function\s+)?)(?:\\?\b[a-z_]\w*)+\b(?!\\)/i,
                    lookbehind: true,
                    inside: {
                        punctuation: /\\/
                    }
                },
                'class-name-definition': {
                    pattern: /(\b(?:class|enum|interface|trait)\s+)\b[a-z_]\w*(?!\\)\b/i,
                    lookbehind: true,
                    alias: 'class-name'
                },
                'function-definition': {
                    pattern: /(\bfunction\s+)[a-z_]\w*(?=\s*\()/i,
                    lookbehind: true,
                    alias: 'function'
                },
                keyword: [ {
                    pattern: /(\(\s*)\b(?:array|bool|boolean|float|int|integer|object|string)\b(?=\s*\))/i,
                    alias: 'type-casting',
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /([(,?]\s*)\b(?:array(?!\s*\()|bool|callable|(?:false|null)(?=\s*\|)|float|int|iterable|mixed|object|self|static|string)\b(?=\s*\$)/i,
                    alias: 'type-hint',
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /(\)\s*:\s*(?:\?\s*)?)\b(?:array(?!\s*\()|bool|callable|(?:false|null)(?=\s*\|)|float|int|iterable|mixed|never|object|self|static|string|void)\b/i,
                    alias: 'return-type',
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /\b(?:array(?!\s*\()|bool|float|int|iterable|mixed|object|string|void)\b/i,
                    alias: 'type-declaration',
                    greedy: true
                }, {
                    pattern: /(\|\s*)(?:false|null)\b|\b(?:false|null)(?=\s*\|)/i,
                    alias: 'type-declaration',
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /\b(?:parent|self|static)(?=\s*::)/i,
                    alias: 'static-context',
                    greedy: true
                }, {
                    pattern: /(\byield\s+)from\b/i,
                    lookbehind: true
                }, /\bclass\b/i, {
                    pattern: /((?:^|[^\s>:]|(?:^|[^-])>|(?:^|[^:]):)\s*)\b(?:abstract|and|array|as|break|callable|case|catch|clone|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|enum|eval|exit|extends|final|finally|fn|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|match|namespace|never|new|or|parent|print|private|protected|public|readonly|require|require_once|return|self|static|switch|throw|trait|try|unset|use|var|while|xor|yield|__halt_compiler)\b/i,
                    lookbehind: true
                } ],
                'argument-name': {
                    pattern: /([(,]\s*)\b[a-z_]\w*(?=\s*:(?!:))/i,
                    lookbehind: true
                },
                'class-name': [ {
                    pattern: /(\b(?:extends|implements|instanceof|new(?!\s+self|\s+static))\s+|\bcatch\s*\()\b[a-z_]\w*(?!\\)\b/i,
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /(\|\s*)\b[a-z_]\w*(?!\\)\b/i,
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /\b[a-z_]\w*(?!\\)\b(?=\s*\|)/i,
                    greedy: true
                }, {
                    pattern: /(\|\s*)(?:\\?\b[a-z_]\w*)+\b/i,
                    alias: 'class-name-fully-qualified',
                    greedy: true,
                    lookbehind: true,
                    inside: {
                        punctuation: /\\/
                    }
                }, {
                    pattern: /(?:\\?\b[a-z_]\w*)+\b(?=\s*\|)/i,
                    alias: 'class-name-fully-qualified',
                    greedy: true,
                    inside: {
                        punctuation: /\\/
                    }
                }, {
                    pattern: /(\b(?:extends|implements|instanceof|new(?!\s+self\b|\s+static\b))\s+|\bcatch\s*\()(?:\\?\b[a-z_]\w*)+\b(?!\\)/i,
                    alias: 'class-name-fully-qualified',
                    greedy: true,
                    lookbehind: true,
                    inside: {
                        punctuation: /\\/
                    }
                }, {
                    pattern: /\b[a-z_]\w*(?=\s*\$)/i,
                    alias: 'type-declaration',
                    greedy: true
                }, {
                    pattern: /(?:\\?\b[a-z_]\w*)+(?=\s*\$)/i,
                    alias: [ 'class-name-fully-qualified', 'type-declaration' ],
                    greedy: true,
                    inside: {
                        punctuation: /\\/
                    }
                }, {
                    pattern: /\b[a-z_]\w*(?=\s*::)/i,
                    alias: 'static-context',
                    greedy: true
                }, {
                    pattern: /(?:\\?\b[a-z_]\w*)+(?=\s*::)/i,
                    alias: [ 'class-name-fully-qualified', 'static-context' ],
                    greedy: true,
                    inside: {
                        punctuation: /\\/
                    }
                }, {
                    pattern: /([(,?]\s*)[a-z_]\w*(?=\s*\$)/i,
                    alias: 'type-hint',
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /([(,?]\s*)(?:\\?\b[a-z_]\w*)+(?=\s*\$)/i,
                    alias: [ 'class-name-fully-qualified', 'type-hint' ],
                    greedy: true,
                    lookbehind: true,
                    inside: {
                        punctuation: /\\/
                    }
                }, {
                    pattern: /(\)\s*:\s*(?:\?\s*)?)\b[a-z_]\w*(?!\\)\b/i,
                    alias: 'return-type',
                    greedy: true,
                    lookbehind: true
                }, {
                    pattern: /(\)\s*:\s*(?:\?\s*)?)(?:\\?\b[a-z_]\w*)+\b(?!\\)/i,
                    alias: [ 'class-name-fully-qualified', 'return-type' ],
                    greedy: true,
                    lookbehind: true,
                    inside: {
                        punctuation: /\\/
                    }
                } ],
                constant,
                function: {
                    pattern: /(^|[^\\\w])\\?[a-z_](?:[\w\\]*\w)?(?=\s*\()/i,
                    lookbehind: true,
                    inside: {
                        punctuation: /\\/
                    }
                },
                property: {
                    pattern: /(->\s*)\w+/,
                    lookbehind: true
                },
                number,
                operator,
                punctuation
            };
            var string_interpolation = {
                pattern: /\{\$(?:\{(?:\{[^{}]+\}|[^{}]+)\}|[^{}])+\}|(^|[^\\{])\$+(?:\w+(?:\[[^\r\n\[\]]+\]|->\w+)?)/,
                lookbehind: true,
                inside: Prism.languages.php
            };
            var string = [ {
                pattern: /<<<'([^']+)'[\r\n](?:.*[\r\n])*?\1;/,
                alias: 'nowdoc-string',
                greedy: true,
                inside: {
                    delimiter: {
                        pattern: /^<<<'[^']+'|[a-z_]\w*;$/i,
                        alias: 'symbol',
                        inside: {
                            punctuation: /^<<<'?|[';]$/
                        }
                    }
                }
            }, {
                pattern: /<<<(?:"([^"]+)"[\r\n](?:.*[\r\n])*?\1;|([a-z_]\w*)[\r\n](?:.*[\r\n])*?\2;)/i,
                alias: 'heredoc-string',
                greedy: true,
                inside: {
                    delimiter: {
                        pattern: /^<<<(?:"[^"]+"|[a-z_]\w*)|[a-z_]\w*;$/i,
                        alias: 'symbol',
                        inside: {
                            punctuation: /^<<<"?|[";]$/
                        }
                    },
                    interpolation: string_interpolation
                }
            }, {
                pattern: /`(?:\\[\s\S]|[^\\`])*`/,
                alias: 'backtick-quoted-string',
                greedy: true
            }, {
                pattern: /'(?:\\[\s\S]|[^\\'])*'/,
                alias: 'single-quoted-string',
                greedy: true
            }, {
                pattern: /"(?:\\[\s\S]|[^\\"])*"/,
                alias: 'double-quoted-string',
                greedy: true,
                inside: {
                    interpolation: string_interpolation
                }
            } ];
            Prism.languages.insertBefore('php', 'variable', {
                string,
                attribute: {
                    pattern: /#\[(?:[^"'\/#]|\/(?![*/])|\/\/.*$|#(?!\[).*$|\/\*(?:[^*]|\*(?!\/))*\*\/|"(?:\\[\s\S]|[^\\"])*"|'(?:\\[\s\S]|[^\\'])*')+\](?=\s*[a-z$#])/im,
                    greedy: true,
                    inside: {
                        'attribute-content': {
                            pattern: /^(#\[)[\s\S]+(?=\]$)/,
                            lookbehind: true,
                            inside: {
                                comment,
                                string,
                                'attribute-class-name': [ {
                                    pattern: /([^:]|^)\b[a-z_]\w*(?!\\)\b/i,
                                    alias: 'class-name',
                                    greedy: true,
                                    lookbehind: true
                                }, {
                                    pattern: /([^:]|^)(?:\\?\b[a-z_]\w*)+/i,
                                    alias: [ 'class-name', 'class-name-fully-qualified' ],
                                    greedy: true,
                                    lookbehind: true,
                                    inside: {
                                        punctuation: /\\/
                                    }
                                } ],
                                constant,
                                number,
                                operator,
                                punctuation
                            }
                        },
                        delimiter: {
                            pattern: /^#\[|\]$/,
                            alias: 'punctuation'
                        }
                    }
                }
            });
            Prism.hooks.add('before-tokenize', (function(env) {
                if (!/<\?/.test(env.code)) {
                    return;
                }
                var phpPattern = /<\?(?:[^"'/#]|\/(?![*/])|("|')(?:\\[\s\S]|(?!\1)[^\\])*\1|(?:\/\/|#(?!\[))(?:[^?\n\r]|\?(?!>))*(?=$|\?>|[\r\n])|#\[|\/\*(?:[^*]|\*(?!\/))*(?:\*\/|$))*?(?:\?>|$)/g;
                Prism.languages['markup-templating'].buildPlaceholders(env, 'php', phpPattern);
            }));
            Prism.hooks.add('after-tokenize', (function(env) {
                Prism.languages['markup-templating'].tokenizePlaceholders(env, 'php');
            }));
        })(Prism);
        (function(Prism) {
            var javaDocLike = Prism.languages.javadoclike = {
                parameter: {
                    pattern: /(^[\t ]*(?:\/{3}|\*|\/\*\*)\s*@(?:arg|arguments|param)\s+)\w+/m,
                    lookbehind: true
                },
                keyword: {
                    pattern: /(^[\t ]*(?:\/{3}|\*|\/\*\*)\s*|\{)@[a-z][a-zA-Z-]+\b/m,
                    lookbehind: true
                },
                punctuation: /[{}]/
            };
            function docCommentSupport(lang, callback) {
                var tokenName = 'doc-comment';
                var grammar = Prism.languages[lang];
                if (!grammar) {
                    return;
                }
                var token = grammar[tokenName];
                if (!token) {
                    var definition = {};
                    definition[tokenName] = {
                        pattern: /(^|[^\\])\/\*\*[^/][\s\S]*?(?:\*\/|$)/,
                        lookbehind: true,
                        alias: 'comment'
                    };
                    grammar = Prism.languages.insertBefore(lang, 'comment', definition);
                    token = grammar[tokenName];
                }
                if (token instanceof RegExp) {
                    token = grammar[tokenName] = {
                        pattern: token
                    };
                }
                if (Array.isArray(token)) {
                    for (var i = 0, l = token.length; i < l; i++) {
                        if (token[i] instanceof RegExp) {
                            token[i] = {
                                pattern: token[i]
                            };
                        }
                        callback(token[i]);
                    }
                } else {
                    callback(token);
                }
            }
            function addSupport(languages, docLanguage) {
                if (typeof languages === 'string') {
                    languages = [ languages ];
                }
                languages.forEach((function(lang) {
                    docCommentSupport(lang, (function(pattern) {
                        if (!pattern.inside) {
                            pattern.inside = {};
                        }
                        pattern.inside.rest = docLanguage;
                    }));
                }));
            }
            Object.defineProperty(javaDocLike, 'addSupport', {
                value: addSupport
            });
            javaDocLike.addSupport([ 'java', 'javascript', 'php' ], javaDocLike);
        })(Prism);
        Prism.languages.json = {
            property: {
                pattern: /(^|[^\\])"(?:\\.|[^\\"\r\n])*"(?=\s*:)/,
                lookbehind: true,
                greedy: true
            },
            string: {
                pattern: /(^|[^\\])"(?:\\.|[^\\"\r\n])*"(?!\s*:)/,
                lookbehind: true,
                greedy: true
            },
            comment: {
                pattern: /\/\/.*|\/\*[\s\S]*?(?:\*\/|$)/,
                greedy: true
            },
            number: /-?\b\d+(?:\.\d+)?(?:e[+-]?\d+)?\b/i,
            punctuation: /[{}[\],]/,
            operator: /:/,
            boolean: /\b(?:false|true)\b/,
            null: {
                pattern: /\bnull\b/,
                alias: 'keyword'
            }
        };
        Prism.languages.webmanifest = Prism.languages.json;
        (function(Prism) {
            var string = /("|')(?:\\(?:\r\n?|\n|.)|(?!\1)[^\\\r\n])*\1/;
            Prism.languages.json5 = Prism.languages.extend('json', {
                property: [ {
                    pattern: RegExp(string.source + '(?=\\s*:)'),
                    greedy: true
                }, {
                    pattern: /(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?=\s*:)/,
                    alias: 'unquoted'
                } ],
                string: {
                    pattern: string,
                    greedy: true
                },
                number: /[+-]?\b(?:NaN|Infinity|0x[a-fA-F\d]+)\b|[+-]?(?:\b\d+(?:\.\d*)?|\B\.\d+)(?:[eE][+-]?\d+\b)?/
            });
        })(Prism);
        Prism.languages.jsonp = Prism.languages.extend('json', {
            punctuation: /[{}[\]();,.]/
        });
        Prism.languages.insertBefore('jsonp', 'punctuation', {
            function: /(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?=\s*\()/
        });
        (function(Prism) {
            var typeExpression = /(?:\b[a-zA-Z]\w*|[|\\[\]])+/.source;
            Prism.languages.phpdoc = Prism.languages.extend('javadoclike', {
                parameter: {
                    pattern: RegExp('(@(?:global|param|property(?:-read|-write)?|var)\\s+(?:' + typeExpression + '\\s+)?)\\$\\w+'),
                    lookbehind: true
                }
            });
            Prism.languages.insertBefore('phpdoc', 'keyword', {
                'class-name': [ {
                    pattern: RegExp('(@(?:global|package|param|property(?:-read|-write)?|return|subpackage|throws|var)\\s+)' + typeExpression),
                    lookbehind: true,
                    inside: {
                        keyword: /\b(?:array|bool|boolean|callback|double|false|float|int|integer|mixed|null|object|resource|self|string|true|void)\b/,
                        punctuation: /[|\\[\]()]/
                    }
                } ]
            });
            Prism.languages.javadoclike.addSupport('php', Prism.languages.phpdoc);
        })(Prism);
        Prism.languages.insertBefore('php', 'variable', {
            this: {
                pattern: /\$this\b/,
                alias: 'keyword'
            },
            global: /\$(?:GLOBALS|HTTP_RAW_POST_DATA|_(?:COOKIE|ENV|FILES|GET|POST|REQUEST|SERVER|SESSION)|argc|argv|http_response_header|php_errormsg)\b/,
            scope: {
                pattern: /\b[\w\\]+::/,
                inside: {
                    keyword: /\b(?:parent|self|static)\b/,
                    punctuation: /::|\\/
                }
            }
        });
        (function(Prism) {
            var javascript = Prism.util.clone(Prism.languages.javascript);
            var space = /(?:\s|\/\/.*(?!.)|\/\*(?:[^*]|\*(?!\/))\*\/)/.source;
            var braces = /(?:\{(?:\{(?:\{[^{}]*\}|[^{}])*\}|[^{}])*\})/.source;
            var spread = /(?:\{<S>*\.{3}(?:[^{}]|<BRACES>)*\})/.source;
            function re(source, flags) {
                source = source.replace(/<S>/g, (function() {
                    return space;
                })).replace(/<BRACES>/g, (function() {
                    return braces;
                })).replace(/<SPREAD>/g, (function() {
                    return spread;
                }));
                return RegExp(source, flags);
            }
            spread = re(spread).source;
            Prism.languages.jsx = Prism.languages.extend('markup', javascript);
            Prism.languages.jsx.tag.pattern = re(/<\/?(?:[\w.:-]+(?:<S>+(?:[\w.:$-]+(?:=(?:"(?:\\[\s\S]|[^\\"])*"|'(?:\\[\s\S]|[^\\'])*'|[^\s{'"/>=]+|<BRACES>))?|<SPREAD>))*<S>*\/?)?>/.source);
            Prism.languages.jsx.tag.inside['tag'].pattern = /^<\/?[^\s>\/]*/;
            Prism.languages.jsx.tag.inside['attr-value'].pattern = /=(?!\{)(?:"(?:\\[\s\S]|[^\\"])*"|'(?:\\[\s\S]|[^\\'])*'|[^\s'">]+)/;
            Prism.languages.jsx.tag.inside['tag'].inside['class-name'] = /^[A-Z]\w*(?:\.[A-Z]\w*)*$/;
            Prism.languages.jsx.tag.inside['comment'] = javascript['comment'];
            Prism.languages.insertBefore('inside', 'attr-name', {
                spread: {
                    pattern: re(/<SPREAD>/.source),
                    inside: Prism.languages.jsx
                }
            }, Prism.languages.jsx.tag);
            Prism.languages.insertBefore('inside', 'special-attr', {
                script: {
                    pattern: re(/=<BRACES>/.source),
                    alias: 'language-javascript',
                    inside: {
                        'script-punctuation': {
                            pattern: /^=(?=\{)/,
                            alias: 'punctuation'
                        },
                        rest: Prism.languages.jsx
                    }
                }
            }, Prism.languages.jsx.tag);
            var stringifyToken = function(token) {
                if (!token) {
                    return '';
                }
                if (typeof token === 'string') {
                    return token;
                }
                if (typeof token.content === 'string') {
                    return token.content;
                }
                return token.content.map(stringifyToken).join('');
            };
            var walkTokens = function(tokens) {
                var openedTags = [];
                for (var i = 0; i < tokens.length; i++) {
                    var token = tokens[i];
                    var notTagNorBrace = false;
                    if (typeof token !== 'string') {
                        if (token.type === 'tag' && token.content[0] && token.content[0].type === 'tag') {
                            if (token.content[0].content[0].content === '</') {
                                if (openedTags.length > 0 && openedTags[openedTags.length - 1].tagName === stringifyToken(token.content[0].content[1])) {
                                    openedTags.pop();
                                }
                            } else {
                                if (token.content[token.content.length - 1].content === '/>') {} else {
                                    openedTags.push({
                                        tagName: stringifyToken(token.content[0].content[1]),
                                        openedBraces: 0
                                    });
                                }
                            }
                        } else if (openedTags.length > 0 && token.type === 'punctuation' && token.content === '{') {
                            openedTags[openedTags.length - 1].openedBraces++;
                        } else if (openedTags.length > 0 && openedTags[openedTags.length - 1].openedBraces > 0 && token.type === 'punctuation' && token.content === '}') {
                            openedTags[openedTags.length - 1].openedBraces--;
                        } else {
                            notTagNorBrace = true;
                        }
                    }
                    if (notTagNorBrace || typeof token === 'string') {
                        if (openedTags.length > 0 && openedTags[openedTags.length - 1].openedBraces === 0) {
                            var plainText = stringifyToken(token);
                            if (i < tokens.length - 1 && (typeof tokens[i + 1] === 'string' || tokens[i + 1].type === 'plain-text')) {
                                plainText += stringifyToken(tokens[i + 1]);
                                tokens.splice(i + 1, 1);
                            }
                            if (i > 0 && (typeof tokens[i - 1] === 'string' || tokens[i - 1].type === 'plain-text')) {
                                plainText = stringifyToken(tokens[i - 1]) + plainText;
                                tokens.splice(i - 1, 1);
                                i--;
                            }
                            tokens[i] = new Prism.Token('plain-text', plainText, null, plainText);
                        }
                    }
                    if (token.content && typeof token.content !== 'string') {
                        walkTokens(token.content);
                    }
                }
            };
            Prism.hooks.add('after-tokenize', (function(env) {
                if (env.language !== 'jsx' && env.language !== 'tsx') {
                    return;
                }
                walkTokens(env.tokens);
            }));
        })(Prism);
        Prism.languages.scss = Prism.languages.extend('css', {
            comment: {
                pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|\/\/.*)/,
                lookbehind: true
            },
            atrule: {
                pattern: /@[\w-](?:\([^()]+\)|[^()\s]|\s+(?!\s))*?(?=\s+[{;])/,
                inside: {
                    rule: /@[\w-]+/
                }
            },
            url: /(?:[-a-z]+-)?url(?=\()/i,
            selector: {
                pattern: /(?=\S)[^@;{}()]?(?:[^@;{}()\s]|\s+(?!\s)|#\{\$[-\w]+\})+(?=\s*\{(?:\}|\s|[^}][^:{}]*[:{][^}]))/,
                inside: {
                    parent: {
                        pattern: /&/,
                        alias: 'important'
                    },
                    placeholder: /%[-\w]+/,
                    variable: /\$[-\w]+|#\{\$[-\w]+\}/
                }
            },
            property: {
                pattern: /(?:[-\w]|\$[-\w]|#\{\$[-\w]+\})+(?=\s*:)/,
                inside: {
                    variable: /\$[-\w]+|#\{\$[-\w]+\}/
                }
            }
        });
        Prism.languages.insertBefore('scss', 'atrule', {
            keyword: [ /@(?:content|debug|each|else(?: if)?|extend|for|forward|function|if|import|include|mixin|return|use|warn|while)\b/i, {
                pattern: /( )(?:from|through)(?= )/,
                lookbehind: true
            } ]
        });
        Prism.languages.insertBefore('scss', 'important', {
            variable: /\$[-\w]+|#\{\$[-\w]+\}/
        });
        Prism.languages.insertBefore('scss', 'function', {
            'module-modifier': {
                pattern: /\b(?:as|hide|show|with)\b/i,
                alias: 'keyword'
            },
            placeholder: {
                pattern: /%[-\w]+/,
                alias: 'selector'
            },
            statement: {
                pattern: /\B!(?:default|optional)\b/i,
                alias: 'keyword'
            },
            boolean: /\b(?:false|true)\b/,
            null: {
                pattern: /\bnull\b/,
                alias: 'keyword'
            },
            operator: {
                pattern: /(\s)(?:[-+*\/%]|[=!]=|<=?|>=?|and|not|or)(?=\s)/,
                lookbehind: true
            }
        });
        Prism.languages.scss['atrule'].inside.rest = Prism.languages.scss;
        Prism.languages.sql = {
            comment: {
                pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|(?:--|\/\/|#).*)/,
                lookbehind: true
            },
            variable: [ {
                pattern: /@(["'`])(?:\\[\s\S]|(?!\1)[^\\])+\1/,
                greedy: true
            }, /@[\w.$]+/ ],
            string: {
                pattern: /(^|[^@\\])("|')(?:\\[\s\S]|(?!\2)[^\\]|\2\2)*\2/,
                greedy: true,
                lookbehind: true
            },
            identifier: {
                pattern: /(^|[^@\\])`(?:\\[\s\S]|[^`\\]|``)*`/,
                greedy: true,
                lookbehind: true,
                inside: {
                    punctuation: /^`|`$/
                }
            },
            function: /\b(?:AVG|COUNT|FIRST|FORMAT|LAST|LCASE|LEN|MAX|MID|MIN|MOD|NOW|ROUND|SUM|UCASE)(?=\s*\()/i,
            keyword: /\b(?:ACTION|ADD|AFTER|ALGORITHM|ALL|ALTER|ANALYZE|ANY|APPLY|AS|ASC|AUTHORIZATION|AUTO_INCREMENT|BACKUP|BDB|BEGIN|BERKELEYDB|BIGINT|BINARY|BIT|BLOB|BOOL|BOOLEAN|BREAK|BROWSE|BTREE|BULK|BY|CALL|CASCADED?|CASE|CHAIN|CHAR(?:ACTER|SET)?|CHECK(?:POINT)?|CLOSE|CLUSTERED|COALESCE|COLLATE|COLUMNS?|COMMENT|COMMIT(?:TED)?|COMPUTE|CONNECT|CONSISTENT|CONSTRAINT|CONTAINS(?:TABLE)?|CONTINUE|CONVERT|CREATE|CROSS|CURRENT(?:_DATE|_TIME|_TIMESTAMP|_USER)?|CURSOR|CYCLE|DATA(?:BASES?)?|DATE(?:TIME)?|DAY|DBCC|DEALLOCATE|DEC|DECIMAL|DECLARE|DEFAULT|DEFINER|DELAYED|DELETE|DELIMITERS?|DENY|DESC|DESCRIBE|DETERMINISTIC|DISABLE|DISCARD|DISK|DISTINCT|DISTINCTROW|DISTRIBUTED|DO|DOUBLE|DROP|DUMMY|DUMP(?:FILE)?|DUPLICATE|ELSE(?:IF)?|ENABLE|ENCLOSED|END|ENGINE|ENUM|ERRLVL|ERRORS|ESCAPED?|EXCEPT|EXEC(?:UTE)?|EXISTS|EXIT|EXPLAIN|EXTENDED|FETCH|FIELDS|FILE|FILLFACTOR|FIRST|FIXED|FLOAT|FOLLOWING|FOR(?: EACH ROW)?|FORCE|FOREIGN|FREETEXT(?:TABLE)?|FROM|FULL|FUNCTION|GEOMETRY(?:COLLECTION)?|GLOBAL|GOTO|GRANT|GROUP|HANDLER|HASH|HAVING|HOLDLOCK|HOUR|IDENTITY(?:COL|_INSERT)?|IF|IGNORE|IMPORT|INDEX|INFILE|INNER|INNODB|INOUT|INSERT|INT|INTEGER|INTERSECT|INTERVAL|INTO|INVOKER|ISOLATION|ITERATE|JOIN|KEYS?|KILL|LANGUAGE|LAST|LEAVE|LEFT|LEVEL|LIMIT|LINENO|LINES|LINESTRING|LOAD|LOCAL|LOCK|LONG(?:BLOB|TEXT)|LOOP|MATCH(?:ED)?|MEDIUM(?:BLOB|INT|TEXT)|MERGE|MIDDLEINT|MINUTE|MODE|MODIFIES|MODIFY|MONTH|MULTI(?:LINESTRING|POINT|POLYGON)|NATIONAL|NATURAL|NCHAR|NEXT|NO|NONCLUSTERED|NULLIF|NUMERIC|OFF?|OFFSETS?|ON|OPEN(?:DATASOURCE|QUERY|ROWSET)?|OPTIMIZE|OPTION(?:ALLY)?|ORDER|OUT(?:ER|FILE)?|OVER|PARTIAL|PARTITION|PERCENT|PIVOT|PLAN|POINT|POLYGON|PRECEDING|PRECISION|PREPARE|PREV|PRIMARY|PRINT|PRIVILEGES|PROC(?:EDURE)?|PUBLIC|PURGE|QUICK|RAISERROR|READS?|REAL|RECONFIGURE|REFERENCES|RELEASE|RENAME|REPEAT(?:ABLE)?|REPLACE|REPLICATION|REQUIRE|RESIGNAL|RESTORE|RESTRICT|RETURN(?:ING|S)?|REVOKE|RIGHT|ROLLBACK|ROUTINE|ROW(?:COUNT|GUIDCOL|S)?|RTREE|RULE|SAVE(?:POINT)?|SCHEMA|SECOND|SELECT|SERIAL(?:IZABLE)?|SESSION(?:_USER)?|SET(?:USER)?|SHARE|SHOW|SHUTDOWN|SIMPLE|SMALLINT|SNAPSHOT|SOME|SONAME|SQL|START(?:ING)?|STATISTICS|STATUS|STRIPED|SYSTEM_USER|TABLES?|TABLESPACE|TEMP(?:ORARY|TABLE)?|TERMINATED|TEXT(?:SIZE)?|THEN|TIME(?:STAMP)?|TINY(?:BLOB|INT|TEXT)|TOP?|TRAN(?:SACTIONS?)?|TRIGGER|TRUNCATE|TSEQUAL|TYPES?|UNBOUNDED|UNCOMMITTED|UNDEFINED|UNION|UNIQUE|UNLOCK|UNPIVOT|UNSIGNED|UPDATE(?:TEXT)?|USAGE|USE|USER|USING|VALUES?|VAR(?:BINARY|CHAR|CHARACTER|YING)|VIEW|WAITFOR|WARNINGS|WHEN|WHERE|WHILE|WITH(?: ROLLUP|IN)?|WORK|WRITE(?:TEXT)?|YEAR)\b/i,
            boolean: /\b(?:FALSE|NULL|TRUE)\b/i,
            number: /\b0x[\da-f]+\b|\b\d+(?:\.\d*)?|\B\.\d+\b/i,
            operator: /[-+*\/=%^~]|&&?|\|\|?|!=?|<(?:=>?|<|>)?|>[>=]?|\b(?:AND|BETWEEN|DIV|ILIKE|IN|IS|LIKE|NOT|OR|REGEXP|RLIKE|SOUNDS LIKE|XOR)\b/i,
            punctuation: /[;[\]()`,.]/
        };
        (function(Prism) {
            Prism.languages.typescript = Prism.languages.extend('javascript', {
                'class-name': {
                    pattern: /(\b(?:class|extends|implements|instanceof|interface|new|type)\s+)(?!keyof\b)(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*(?:\s*<(?:[^<>]|<(?:[^<>]|<[^<>]*>)*>)*>)?/,
                    lookbehind: true,
                    greedy: true,
                    inside: null
                },
                builtin: /\b(?:Array|Function|Promise|any|boolean|console|never|number|string|symbol|unknown)\b/
            });
            Prism.languages.typescript.keyword.push(/\b(?:abstract|declare|is|keyof|readonly|require)\b/, /\b(?:asserts|infer|interface|module|namespace|type)\b(?=\s*(?:[{_$a-zA-Z\xA0-\uFFFF]|$))/, /\btype\b(?=\s*(?:[\{*]|$))/);
            delete Prism.languages.typescript['parameter'];
            delete Prism.languages.typescript['literal-property'];
            var typeInside = Prism.languages.extend('typescript', {});
            delete typeInside['class-name'];
            Prism.languages.typescript['class-name'].inside = typeInside;
            Prism.languages.insertBefore('typescript', 'function', {
                decorator: {
                    pattern: /@[$\w\xA0-\uFFFF]+/,
                    inside: {
                        at: {
                            pattern: /^@/,
                            alias: 'operator'
                        },
                        function: /^[\s\S]+/
                    }
                },
                'generic-function': {
                    pattern: /#?(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*\s*<(?:[^<>]|<(?:[^<>]|<[^<>]*>)*>)*>(?=\s*\()/,
                    greedy: true,
                    inside: {
                        function: /^#?(?!\s)[_$a-zA-Z\xA0-\uFFFF](?:(?!\s)[$\w\xA0-\uFFFF])*/,
                        generic: {
                            pattern: /<[\s\S]+/,
                            alias: 'class-name',
                            inside: typeInside
                        }
                    }
                }
            });
            Prism.languages.ts = Prism.languages.typescript;
        })(Prism);
        (function() {
            if (typeof Prism === 'undefined' || typeof document === 'undefined') {
                return;
            }
            var PLUGIN_NAME = 'line-numbers';
            var NEW_LINE_EXP = /\n(?!$)/g;
            var config = Prism.plugins.lineNumbers = {
                getLine: function(element, number) {
                    if (element.tagName !== 'PRE' || !element.classList.contains(PLUGIN_NAME)) {
                        return;
                    }
                    var lineNumberRows = element.querySelector('.line-numbers-rows');
                    if (!lineNumberRows) {
                        return;
                    }
                    var lineNumberStart = parseInt(element.getAttribute('data-start'), 10) || 1;
                    var lineNumberEnd = lineNumberStart + (lineNumberRows.children.length - 1);
                    if (number < lineNumberStart) {
                        number = lineNumberStart;
                    }
                    if (number > lineNumberEnd) {
                        number = lineNumberEnd;
                    }
                    var lineIndex = number - lineNumberStart;
                    return lineNumberRows.children[lineIndex];
                },
                resize: function(element) {
                    resizeElements([ element ]);
                },
                assumeViewportIndependence: true
            };
            function resizeElements(elements) {
                elements = elements.filter((function(e) {
                    var codeStyles = getStyles(e);
                    var whiteSpace = codeStyles['white-space'];
                    return whiteSpace === 'pre-wrap' || whiteSpace === 'pre-line';
                }));
                if (elements.length == 0) {
                    return;
                }
                var infos = elements.map((function(element) {
                    var codeElement = element.querySelector('code');
                    var lineNumbersWrapper = element.querySelector('.line-numbers-rows');
                    if (!codeElement || !lineNumbersWrapper) {
                        return undefined;
                    }
                    var lineNumberSizer = element.querySelector('.line-numbers-sizer');
                    var codeLines = codeElement.textContent.split(NEW_LINE_EXP);
                    if (!lineNumberSizer) {
                        lineNumberSizer = document.createElement('span');
                        lineNumberSizer.className = 'line-numbers-sizer';
                        codeElement.appendChild(lineNumberSizer);
                    }
                    lineNumberSizer.innerHTML = '0';
                    lineNumberSizer.style.display = 'block';
                    var oneLinerHeight = lineNumberSizer.getBoundingClientRect().height;
                    lineNumberSizer.innerHTML = '';
                    return {
                        element,
                        lines: codeLines,
                        lineHeights: [],
                        oneLinerHeight,
                        sizer: lineNumberSizer
                    };
                })).filter(Boolean);
                infos.forEach((function(info) {
                    var lineNumberSizer = info.sizer;
                    var lines = info.lines;
                    var lineHeights = info.lineHeights;
                    var oneLinerHeight = info.oneLinerHeight;
                    lineHeights[lines.length - 1] = undefined;
                    lines.forEach((function(line, index) {
                        if (line && line.length > 1) {
                            var e = lineNumberSizer.appendChild(document.createElement('span'));
                            e.style.display = 'block';
                            e.textContent = line;
                        } else {
                            lineHeights[index] = oneLinerHeight;
                        }
                    }));
                }));
                infos.forEach((function(info) {
                    var lineNumberSizer = info.sizer;
                    var lineHeights = info.lineHeights;
                    var childIndex = 0;
                    for (var i = 0; i < lineHeights.length; i++) {
                        if (lineHeights[i] === undefined) {
                            lineHeights[i] = lineNumberSizer.children[childIndex++].getBoundingClientRect().height;
                        }
                    }
                }));
                infos.forEach((function(info) {
                    var lineNumberSizer = info.sizer;
                    var wrapper = info.element.querySelector('.line-numbers-rows');
                    lineNumberSizer.style.display = 'none';
                    lineNumberSizer.innerHTML = '';
                    info.lineHeights.forEach((function(height, lineNumber) {
                        wrapper.children[lineNumber].style.height = height + 'px';
                    }));
                }));
            }
            function getStyles(element) {
                if (!element) {
                    return null;
                }
                return window.getComputedStyle ? getComputedStyle(element) : element.currentStyle || null;
            }
            var lastWidth = undefined;
            window.addEventListener('resize', (function() {
                if (config.assumeViewportIndependence && lastWidth === window.innerWidth) {
                    return;
                }
                lastWidth = window.innerWidth;
                resizeElements(Array.prototype.slice.call(document.querySelectorAll('pre.' + PLUGIN_NAME)));
            }));
            Prism.hooks.add('complete', (function(env) {
                if (!env.code) {
                    return;
                }
                var code = env.element;
                var pre = code.parentNode;
                if (!pre || !/pre/i.test(pre.nodeName)) {
                    return;
                }
                if (code.querySelector('.line-numbers-rows')) {
                    return;
                }
                if (!Prism.util.isActive(code, PLUGIN_NAME)) {
                    return;
                }
                code.classList.remove(PLUGIN_NAME);
                pre.classList.add(PLUGIN_NAME);
                var match = env.code.match(NEW_LINE_EXP);
                var linesNum = match ? match.length + 1 : 1;
                var lineNumbersWrapper;
                var lines = new Array(linesNum + 1).join('<span></span>');
                lineNumbersWrapper = document.createElement('span');
                lineNumbersWrapper.setAttribute('aria-hidden', 'true');
                lineNumbersWrapper.className = 'line-numbers-rows';
                lineNumbersWrapper.innerHTML = lines;
                if (pre.hasAttribute('data-start')) {
                    pre.style.counterReset = 'linenumber ' + (parseInt(pre.getAttribute('data-start'), 10) - 1);
                }
                env.element.appendChild(lineNumbersWrapper);
                resizeElements([ pre ]);
                Prism.hooks.run('line-numbers', env);
            }));
            Prism.hooks.add('line-numbers', (function(env) {
                env.plugins = env.plugins || {};
                env.plugins.lineNumbers = true;
            }));
        })();
    }
};

var __webpack_module_cache__ = {};

function __webpack_require__(moduleId) {
    var cachedModule = __webpack_module_cache__[moduleId];
    if (cachedModule !== undefined) {
        return cachedModule.exports;
    }
    var module = __webpack_module_cache__[moduleId] = {
        exports: {}
    };
    __webpack_modules__[moduleId](module, module.exports, __webpack_require__);
    return module.exports;
}

(() => {
    __webpack_require__.g = function() {
        if (typeof globalThis === 'object') return globalThis;
        try {
            return this || new Function('return this')();
        } catch (e) {
            if (typeof window === 'object') return window;
        }
    }();
})();

var __webpack_exports__ = __webpack_require__(491);